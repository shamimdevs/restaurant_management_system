<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'branch_id'                      => 'nullable|integer|exists:branches,id',
            'order_type'                     => 'required|in:dine_in,takeaway,delivery,qr_order',
            'table_session_id'               => 'nullable|integer|exists:table_sessions,id',
            'customer_id'                    => 'nullable|integer|exists:customers,id',
            'coupon_code'                    => 'nullable|string|max:50',
            'loyalty_redeem_points'          => 'nullable|integer|min:0',
            'notes'                          => 'nullable|string|max:500',
            'delivery_address'               => 'nullable|string',
            'delivery_area'                  => 'nullable|string|max:100',
            'delivery_fee'                   => 'nullable|numeric|min:0',
            'source'                         => 'nullable|in:pos,qr,online,phone',

            'items'                          => 'required|array|min:1',
            'items.*.menu_item_id'           => 'required|integer|exists:menu_items,id',
            'items.*.variant_id'             => 'nullable|integer|exists:menu_item_variants,id',
            'items.*.quantity'               => 'required|integer|min:1|max:100',
            'items.*.notes'                  => 'nullable|string|max:200',
            'items.*.modifiers'              => 'nullable|array',
            'items.*.modifiers.*.modifier_id'=> 'required|integer|exists:modifiers,id',
            'items.*.modifiers.*.quantity'   => 'nullable|integer|min:1',

            'payments'                       => 'nullable|array',
            'payments.*.method'              => 'required|in:cash,card,bkash,nagad,rocket,upay,loyalty_points',
            'payments.*.amount'              => 'required|numeric|min:0.01',
            'payments.*.reference'           => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.*.menu_item_id.exists' => 'One or more menu items are invalid.',
        ];
    }
}
