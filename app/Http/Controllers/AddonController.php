<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonRequest;
use App\Http\Requests\UpdateAddonRequest;
use App\Http\Resources\AddonResource;
use App\Models\Addon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AddonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $addons = Addon::with('inventoryItem')
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return AddonResource::collection($addons);
    }

    public function store(StoreAddonRequest $request): AddonResource
    {
        $addon = Addon::create($request->validated());

        return new AddonResource($addon->load('inventoryItem'));
    }

    public function show(Addon $addon): AddonResource
    {
        return new AddonResource($addon->load('inventoryItem'));
    }

    public function update(UpdateAddonRequest $request, Addon $addon): AddonResource
    {
        $addon->update($request->validated());

        return new AddonResource($addon->load('inventoryItem'));
    }

    public function destroy(Addon $addon): Response
    {
        $addon->delete();

        return response()->noContent();
    }
}
