<?php

namespace App\Http\Requests;

use App\Enums\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', Rule::enum(ProductCategory::class)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'addon_ids' => ['sometimes', 'array'],
            'addon_ids.*' => ['integer', 'exists:addons,id'],
            'ingredients' => ['sometimes', 'array'],
            'ingredients.*.inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id', 'distinct'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
