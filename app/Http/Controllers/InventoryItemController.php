<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InventoryItemController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return InventoryItemResource::collection(
            InventoryItem::query()->orderBy('name')->get()
        );
    }

    public function store(StoreInventoryItemRequest $request): InventoryItemResource
    {
        return new InventoryItemResource(InventoryItem::create($request->validated()));
    }

    public function show(InventoryItem $inventoryItem): InventoryItemResource
    {
        return new InventoryItemResource($inventoryItem);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem->update($request->validated());

        return new InventoryItemResource($inventoryItem);
    }

    public function destroy(InventoryItem $inventoryItem): Response
    {
        $inventoryItem->delete();

        return response()->noContent();
    }
}
