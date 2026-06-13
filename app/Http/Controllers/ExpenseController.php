<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ExpenseController extends Controller
{
    public function index(Request $request, ReportService $reports): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'in:today,week,month,year'],
            'from' => ['sometimes', 'required_with:to', 'date'],
            'to' => ['sometimes', 'required_with:from', 'date', 'after_or_equal:from'],
        ]);

        $query = Expense::query()->orderByDesc('date')->orderByDesc('id');

        if (isset($validated['from'], $validated['to'])) {
            $query->whereBetween('date', [$validated['from'], $validated['to']]);
        } elseif (isset($validated['period'])) {
            $range = $reports->range($validated['period']);
            $query->whereBetween('date', [$range[0]->toDateString(), $range[1]->toDateString()]);
        }

        $total = (float) (clone $query)->sum('total_amount');

        return ExpenseResource::collection($query->paginate(15))
            ->additional(['total_expenses' => $total]);
    }

    public function store(StoreExpenseRequest $request): ExpenseResource
    {
        $data = $request->validated();
        $data['total_amount'] = round($data['amount'] * $data['quantity'], 2);

        return new ExpenseResource(Expense::create($data));
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $data = $request->validated();
        $data['total_amount'] = round($data['amount'] * $data['quantity'], 2);

        $expense->update($data);

        return new ExpenseResource($expense);
    }

    public function destroy(Expense $expense): Response
    {
        $expense->delete();

        return response()->noContent();
    }
}
