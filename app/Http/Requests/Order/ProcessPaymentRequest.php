<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payments'           => 'required|array|min:1',
            'payments.*.method'  => 'required|in:cash,card,bkash,nagad,rocket,upay,bank_transfer,loyalty_points',
            'payments.*.amount'  => 'required|numeric|min:0.01',
            'payments.*.reference' => 'nullable|string|max:100',
        ];
    }
}
