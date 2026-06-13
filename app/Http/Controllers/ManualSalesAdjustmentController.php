<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManualSalesAdjustmentRequest;
use App\Http\Requests\UpdateManualSalesAdjustmentRequest;
use App\Http\Resources\ManualSalesAdjustmentResource;
use App\Models\ManualSalesAdjustment;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ManualSalesAdjustmentController extends Controller
{
    public function index(Request $request, ReportService $reports): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'in:today,week,month,year'],
            'from' => ['sometimes', 'required_with:to', 'date'],
            'to' => ['sometimes', 'required_with:from', 'date', 'after_or_equal:from'],
        ]);

        $query = ManualSalesAdjustment::query()->orderByDesc('date')->orderByDesc('id');

        if (isset($validated['from'], $validated['to'])) {
            $query->whereBetween('date', [$validated['from'], $validated['to']]);
        } elseif (isset($validated['period'])) {
            $range = $reports->range($validated['period']);
            $query->whereBetween('date', [$range[0]->toDateString(), $range[1]->toDateString()]);
        }

        $total = (float) (clone $query)->sum('amount');

        return ManualSalesAdjustmentResource::collection($query->paginate(15))
            ->additional(['total_manual_sales' => $total]);
    }

    public function store(StoreManualSalesAdjustmentRequest $request): ManualSalesAdjustmentResource
    {
        return new ManualSalesAdjustmentResource(ManualSalesAdjustment::create($request->validated()));
    }

    public function show(ManualSalesAdjustment $manualSalesAdjustment): ManualSalesAdjustmentResource
    {
        return new ManualSalesAdjustmentResource($manualSalesAdjustment);
    }

    public function update(
        UpdateManualSalesAdjustmentRequest $request,
        ManualSalesAdjustment $manualSalesAdjustment,
    ): ManualSalesAdjustmentResource {
        $manualSalesAdjustment->update($request->validated());

        return new ManualSalesAdjustmentResource($manualSalesAdjustment);
    }

    public function destroy(ManualSalesAdjustment $manualSalesAdjustment): Response
    {
        $manualSalesAdjustment->delete();

        return response()->noContent();
    }
}
