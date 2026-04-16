<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('menuItem')?->id;
        return [
            'category_id'     => 'nullable|integer|exists:categories,id',
            'tax_group_id'    => 'nullable|integer|exists:tax_groups,id',
            'name'            => 'nullable|string|max:150',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|max:2048',
            'sku'             => "nullable|string|max:50|unique:menu_items,sku,{$id}",
            'base_price'      => 'nullable|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'type'            => 'nullable|in:food,beverage,dessert,combo,other',
            'preparation_time'=> 'nullable|integer|min:0',
            'is_available'    => 'boolean',
            'is_featured'     => 'boolean',
            'sort_order'      => 'nullable|integer',
            'tags'            => 'nullable|array',
            'modifier_group_ids' => 'nullable|array',
            'modifier_group_ids.*' => 'integer|exists:modifier_groups,id',
        ];
    }
}
