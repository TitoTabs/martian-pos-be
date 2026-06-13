<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportRange;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ManualSalesAdjustmentResource;
use App\Http\Resources\SaleResource;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\ManualSalesAdjustment;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ResolvesReportRange;

    public function __construct(private readonly ReportService $reports)
    {
    }

    public function sales(Request $request): JsonResponse
    {
        $range = $this->resolveReportRange($request, $this->reports);

        $paymentMethod = $request->validate([
            'payment_method' => ['sometimes', 'in:cash,gcash,card'],
        ])['payment_method'] ?? null;

        $sales = Sale::with('items.addons')
            ->whereBetween('created_at', $range->timestamps())
            ->notCancelled()
            ->when($paymentMethod, fn ($query) => $query->where('payment_method', $paymentMethod))
            ->latest()
            ->get();

        $adjustments = ManualSalesAdjustment::whereBetween('date', $range->dates())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                ...$this->reports->salesSummary($range, $paymentMethod),
                'sales' => SaleResource::collection($sales),
                'manual_adjustments' => ManualSalesAdjustmentResource::collection($adjustments),
            ],
        ]);
    }

    public function expenses(Request $request): JsonResponse
    {
        $range = $this->resolveReportRange($request, $this->reports);
        $expenses = Expense::whereBetween('date', $range->dates())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'total_expenses' => $this->reports->totalExpenses($range),
                'expenses' => ExpenseResource::collection($expenses),
            ],
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $range = $this->resolveReportRange($request, $this->reports);

        $usage = collect($this->reports->inventoryUsage($range))
            ->keyBy('inventory_item_id');

        $items = InventoryItem::query()
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'unit' => $item->unit,
                'stock' => (float) $item->stock,
                'min_stock' => (float) $item->min_stock,
                'cost_per_unit' => (float) $item->cost_per_unit,
                'used' => $usage[$item->id]['used'] ?? 0,
                'is_active' => $item->is_active,
            ]);

        return response()->json([
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function savings(Request $request): JsonResponse
    {
        $range = $this->resolveReportRange($request, $this->reports);
        $summary = $this->reports->salesSummary($range);

        return response()->json([
            'data' => [
                'pos_sales_total' => $summary['pos_sales_total'],
                'manual_sales_total' => $summary['manual_sales_total'],
                'total_sales' => $summary['total_sales'],
                'total_expenses' => $this->reports->totalExpenses($range),
            ],
        ]);
    }
}
