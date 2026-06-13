<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * Barista queue: active orders first-in-first-out, or recent
     * completed orders as history.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $view = $request->validate([
            'view' => ['sometimes', 'in:active,completed'],
        ])['view'] ?? 'active';

        $query = Sale::with('items.addons');

        $orders = $view === 'completed'
            ? $query->where('status', 'completed')->latest()->limit(20)->get()
            : $query->whereIn('status', Sale::ACTIVE_STATUSES)->oldest()->get();

        return SaleResource::collection($orders);
    }

    public function updateStatus(Request $request, Sale $sale): SaleResource
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,preparing,ready,completed'],
        ]);

        $sale->update($data);

        return new SaleResource($sale->load('items.addons'));
    }
}
