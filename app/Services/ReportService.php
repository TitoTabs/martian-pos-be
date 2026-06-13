<?php

namespace App\Services;

use App\Enums\ProductCategory;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\ManualSalesAdjustment;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Resolve a period keyword into a [start, end] date range.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(string $period): array
    {
        return match ($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
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
     * @param array{0: Carbon, 1: Carbon} $range
     * @return array{pos_sales_total: float, manual_sales_total: float, total_sales: float, total_orders: int, total_items_sold: int}
     */
    public function salesSummary(array $range): array
    {
        $posSales = (float) $this->revenueItems($range)->sum('line_total');
        $manualSales = $this->manualSalesTotal($range);

        return [
            'pos_sales_total' => $posSales,
            'manual_sales_total' => $manualSales,
            'total_sales' => round($posSales + $manualSales, 2),
            // Orders that contain at least one revenue-counting item.
            'total_orders' => Sale::whereBetween('created_at', $range)
                ->notCancelled()
                ->whereHas('items', $this->excludesNonRevenue())
                ->count(),
            'total_items_sold' => (int) $this->revenueItems($range)->sum('quantity'),
        ];
    }

    /**
     * Sale items that count toward revenue: within range, on non-cancelled
     * sales, excluding non-revenue categories (Pastries). Items whose
     * product was since deleted still count (treated as revenue).
     *
     * @param  array{0: Carbon, 1: Carbon}  $range
     * @return Builder<SaleItem>
     */
    private function revenueItems(array $range): Builder
    {
        return SaleItem::whereBetween('created_at', $range)
            ->whereHas('sale', fn (Builder $query) => $query->notCancelled())
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

    /**
     * @param array{0: Carbon, 1: Carbon} $range
     */
    public function manualSalesTotal(array $range): float
    {
        return (float) ManualSalesAdjustment::whereBetween('date', [
            $range[0]->toDateString(),
            $range[1]->toDateString(),
        ])->sum('amount');
    }

    /**
     * @param array{0: Carbon, 1: Carbon} $range
     */
    public function totalExpenses(array $range): float
    {
        return (float) Expense::whereBetween('date', [
            $range[0]->toDateString(),
            $range[1]->toDateString(),
        ])->sum('total_amount');
    }

    /**
     * Best-selling products by quantity within the range.
     *
     * @param array{0: Carbon, 1: Carbon} $range
     * @return array<int, array{name: string, quantity_sold: int, revenue: float}>
     */
    public function topProducts(array $range, int $limit = 5): array
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
     * Inventory usage within the range, derived from product recipes
     * and add-on links applied to what was sold.
     *
     * @param array{0: Carbon, 1: Carbon} $range
     * @return array<int, array{inventory_item_id: int, name: string, unit: string, used: float}>
     */
    public function inventoryUsage(array $range): array
    {
        $fromRecipes = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_ingredients', 'product_ingredients.product_id', '=', 'sale_items.product_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'product_ingredients.inventory_item_id')
            ->where('sales.status', '!=', Sale::CANCELLED)
            ->whereBetween('sale_items.created_at', $range)
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
            ->whereBetween('sale_items.created_at', $range)
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
