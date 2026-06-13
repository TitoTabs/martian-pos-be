<?php

namespace App\Services;

use App\Enums\ProductCategory;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\ManualSalesAdjustment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\ReportRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const DEFAULT_SHIFT_START = '21:00';

    public const DEFAULT_SHIFT_END = '03:00';

    /**
     * Resolve a period keyword into a business-shift range (Asia/Manila).
     *
     * The store's operating shift can cross midnight (default 21:00 → 03:00),
     * so a "business day" runs from the shift start to the next morning's
     * shift end. The current business date is the day the active/most-recent
     * shift started.
     */
    public function range(
        string $period,
        string $startTime = self::DEFAULT_SHIFT_START,
        string $endTime = self::DEFAULT_SHIFT_END,
    ): ReportRange {
        // Business date = today if the shift has started today, else yesterday
        // (covers the post-midnight tail of yesterday's shift and daytime hours).
        $businessDate = now()->gte(now()->setTimeFromTimeString($startTime))
            ? now()->startOfDay()
            : now()->subDay()->startOfDay();

        [$firstDate, $lastDate] = match ($period) {
            'week' => [$businessDate->copy()->startOfWeek(), $businessDate->copy()->endOfWeek()],
            'month' => [$businessDate->copy()->startOfMonth(), $businessDate->copy()->endOfMonth()],
            'year' => [$businessDate->copy()->startOfYear(), $businessDate->copy()->endOfYear()],
            default => [$businessDate->copy(), $businessDate->copy()],
        };

        return $this->shiftRange($firstDate, $lastDate, $startTime, $endTime);
    }

    /**
     * Resolve an explicit custom business-date range into a shift range.
     */
    public function customRange(
        string $startDate,
        string $endDate,
        string $startTime = self::DEFAULT_SHIFT_START,
        string $endTime = self::DEFAULT_SHIFT_END,
    ): ReportRange {
        return $this->shiftRange(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->startOfDay(),
            $startTime,
            $endTime,
        );
    }

    /**
     * Build a ReportRange spanning the shifts of the given business dates.
     * If the shift end is at/after the start (crosses midnight), the closing
     * time belongs to the next calendar morning.
     */
    private function shiftRange(Carbon $firstDate, Carbon $lastDate, string $startTime, string $endTime): ReportRange
    {
        $start = $firstDate->copy()->setTimeFromTimeString($startTime);
        $end = $lastDate->copy()->setTimeFromTimeString($endTime);

        if ($endTime <= $startTime) {
            $end->addDay();
        }

        return new ReportRange(
            start: $start,
            end: $end,
            dateStart: $firstDate->toDateString(),
            dateEnd: $lastDate->toDateString(),
        );
    }

    /**
     * Financial sales summary: total_sales combines real POS sales with
     * manual sales adjustments, while orders/items reflect POS only.
     *
     * Revenue figures are computed from sale *items* (not Sale::total) so
     * non-revenue categories like Pastries — which an order may contain
     * alongside coffee — are excluded from every business performance
     * number while the order itself still exists in history.
     *
     * @return array{pos_sales_total: float, manual_sales_total: float, total_sales: float, total_orders: int, total_items_sold: int}
     */
    public function salesSummary(ReportRange $range, ?string $paymentMethod = null): array
    {
        $posSales = (float) $this->revenueItems($range, $paymentMethod)->sum('line_total');
        $manualSales = $this->manualSalesTotal($range);

        return [
            'pos_sales_total' => $posSales,
            'manual_sales_total' => $manualSales,
            'total_sales' => round($posSales + $manualSales, 2),
            // Orders that contain at least one revenue-counting item.
            'total_orders' => Sale::whereBetween('created_at', $range->timestamps())
                ->notCancelled()
                ->when($paymentMethod, fn (Builder $query) => $query->where('payment_method', $paymentMethod))
                ->whereHas('items', $this->excludesNonRevenue())
                ->count(),
            'total_items_sold' => (int) $this->revenueItems($range, $paymentMethod)->sum('quantity'),
        ];
    }

    /**
     * Sale items that count toward revenue: within the shift, on non-cancelled
     * sales, excluding non-revenue categories (Pastries). Items whose product
     * was since deleted still count (treated as revenue). An optional payment
     * method narrows to that tender.
     *
     * @return Builder<SaleItem>
     */
    private function revenueItems(ReportRange $range, ?string $paymentMethod = null): Builder
    {
        return SaleItem::whereBetween('created_at', $range->timestamps())
            ->whereHas('sale', fn (Builder $query) => $query->notCancelled()
                ->when($paymentMethod, fn (Builder $q) => $q->where('payment_method', $paymentMethod)))
            ->where($this->excludesNonRevenue());
    }

    /**
     * Constraint excluding sale items whose product is a non-revenue
     * category. `whereDoesntHave` keeps items with no product (deleted)
     * as revenue rather than dropping them.
     */
    private function excludesNonRevenue(): \Closure
    {
        return fn (Builder $query) => $query->whereDoesntHave(
            'product',
            fn (Builder $product) => $product->whereIn('category', ProductCategory::nonRevenue()),
        );
    }

    public function manualSalesTotal(ReportRange $range): float
    {
        return (float) ManualSalesAdjustment::whereBetween('date', $range->dates())->sum('amount');
    }

    public function totalExpenses(ReportRange $range): float
    {
        return (float) Expense::whereBetween('date', $range->dates())->sum('total_amount');
    }

    /**
     * Best-selling products by quantity within the shift.
     *
     * @return array<int, array{name: string, quantity_sold: int, revenue: float}>
     */
    public function topProducts(ReportRange $range, int $limit = 5): array
    {
        return $this->revenueItems($range)
            ->groupBy('product_name')
            ->selectRaw('product_name, SUM(quantity) as quantity_sold, SUM(line_total) as revenue')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * Active inventory items at or below their minimum stock level.
     *
     * @return Collection<int, InventoryItem>
     */
    public function lowStockInventory(): Collection
    {
        return InventoryItem::where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderByRaw('stock - min_stock')
            ->get();
    }

    /**
     * Inventory usage within the shift, derived from product recipes
     * and add-on links applied to what was sold.
     *
     * @return array<int, array{inventory_item_id: int, name: string, unit: string, used: float}>
     */
    public function inventoryUsage(ReportRange $range): array
    {
        $fromRecipes = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_ingredients', 'product_ingredients.product_id', '=', 'sale_items.product_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'product_ingredients.inventory_item_id')
            ->where('sales.status', '!=', Sale::CANCELLED)
            ->whereBetween('sale_items.created_at', $range->timestamps())
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.unit')
            ->selectRaw('inventory_items.id, inventory_items.name, inventory_items.unit, SUM(sale_items.quantity * product_ingredients.quantity) as used')
            ->get();

        $fromAddons = DB::table('sale_item_addons')
            ->join('sale_items', 'sale_items.id', '=', 'sale_item_addons.sale_item_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('addons', 'addons.id', '=', 'sale_item_addons.addon_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'addons.inventory_item_id')
            ->whereNotNull('addons.inventory_item_id')
            ->where('sales.status', '!=', Sale::CANCELLED)
            ->whereBetween('sale_items.created_at', $range->timestamps())
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.unit')
            ->selectRaw('inventory_items.id, inventory_items.name, inventory_items.unit, SUM(sale_items.quantity * addons.quantity_used) as used')
            ->get();

        return $fromRecipes->concat($fromAddons)
            ->groupBy('id')
            ->map(fn ($rows) => [
                'inventory_item_id' => (int) $rows->first()->id,
                'name' => $rows->first()->name,
                'unit' => $rows->first()->unit,
                'used' => round($rows->sum('used'), 2),
            ])
            ->sortByDesc('used')
            ->values()
            ->all();
    }
}
