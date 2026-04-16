<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreIngredientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'unit_id'         => 'required|integer|exists:units,id',
            'name'            => 'required|string|max:150',
            'description'     => 'nullable|string',
            'sku'             => 'nullable|string|max:50|unique:ingredients,sku',
            'cost_per_unit'   => 'required|numeric|min:0',
            'current_stock'   => 'nullable|numeric|min:0',
            'min_stock_level' => 'required|numeric|min:0',
            'max_stock_level' => 'nullable|numeric',
            'reorder_point'   => 'nullable|numeric',
            'storage_location'=> 'nullable|string|max:100',
            'track_stock'     => 'boolean',
        ];
    }
}
