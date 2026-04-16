<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id'     => 'required|integer|exists:categories,id',
            'tax_group_id'    => 'nullable|integer|exists:tax_groups,id',
            'name'            => 'required|string|max:150',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|max:2048',
            'sku'             => 'nullable|string|max:50|unique:menu_items,sku',
            'barcode'         => 'nullable|string|max:100|unique:menu_items,barcode',
            'base_price'      => 'required|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'type'            => 'in:food,beverage,dessert,combo,other',
            'preparation_time'=> 'nullable|integer|min:0',
            'unit'            => 'nullable|string|max:30',
            'is_available'    => 'boolean',
            'is_featured'     => 'boolean',
            'track_inventory' => 'boolean',
            'sort_order'      => 'nullable|integer',
            'tags'            => 'nullable|array',
            'branch_id'       => 'nullable|integer|exists:branches,id',
            'modifier_group_ids' => 'nullable|array',
            'modifier_group_ids.*' => 'integer|exists:modifier_groups,id',
        ];
    }
}
