<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $sales = Sale::with('items.addons')->notCancelled()->latest()->paginate(15);

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request, SaleService $saleService): SaleResource
    {
        $sale = $saleService->create($request->validated());

        return new SaleResource($sale);
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load('items.addons'));
    }
}
