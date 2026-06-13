<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseResource;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\SaleResource;
use App\Models\Expense;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): JsonResponse
    {
        $period = $request->validate([
            'period' => ['sometimes', 'in:today,week,month,year'],
        ])['period'] ?? 'today';

        $range = $reports->range($period);
        $summary = $reports->salesSummary($range);

        return response()->json([
            'data' => [
                'period' => $period,
                ...$summary,
                'total_expenses' => $reports->totalExpenses($range),
                'top_products' => $reports->topProducts($range),
                'low_stock' => InventoryItemResource::collection($reports->lowStockInventory()),
                'recent_sales' => SaleResource::collection(
                    Sale::with('items.addons')->notCancelled()->latest()->limit(5)->get()
                ),
                'recent_expenses' => ExpenseResource::collection(
                    Expense::query()->orderByDesc('date')->orderByDesc('id')->limit(5)->get()
                ),
            ],
        ]);
    }
}
