<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['addons', 'ingredients'])
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name');

        if ($request->boolean('all')) {
            return ProductResource::collection($query->get());
        }

        return ProductResource::collection($query->paginate(15));
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $data = $request->validated();

        $product = Product::create(Arr::except($data, ['addon_ids', 'ingredients']));
        $this->syncRelations($product, $data);

        return new ProductResource($product->load(['addons', 'ingredients']));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['addons', 'ingredients']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        $product->update(Arr::except($data, ['addon_ids', 'ingredients']));
        $this->syncRelations($product, $data);

        return new ProductResource($product->load(['addons', 'ingredients']));
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function syncRelations(Product $product, array $data): void
    {
        if (array_key_exists('addon_ids', $data)) {
            $product->addons()->sync($data['addon_ids']);
        }

        if (array_key_exists('ingredients', $data)) {
            $product->ingredients()->sync(
                collect($data['ingredients'])->mapWithKeys(fn (array $ingredient) => [
                    $ingredient['inventory_item_id'] => ['quantity' => $ingredient['quantity']],
                ])->all()
            );
        }
    }
}
