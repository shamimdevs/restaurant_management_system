<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:150',
            'phone'             => 'required|string|max:20',
            'email'             => 'nullable|email',
            'nid_number'        => 'nullable|string|max:30',
            'gender'            => 'nullable|in:male,female,other',
            'date_of_birth'     => 'nullable|date',
            'joining_date'      => 'required|date',
            'department_id'     => 'nullable|exists:departments,id',
            'designation_id'    => 'nullable|exists:designations,id',
            'salary_type'       => 'in:monthly,daily,hourly',
            'basic_salary'      => 'required|numeric|min:0',
            'allowances'        => 'nullable|array',
            'allowances.*.name' => 'required|string',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions'        => 'nullable|array',
            'deductions.*.name' => 'required|string',
            'deductions.*.amount' => 'required|numeric|min:0',
            'address'           => 'nullable|string',
            'photo'             => 'nullable|image|max:1024',
            'emergency_contact' => 'nullable|array',
            'bank_account'      => 'nullable|array',
        ];
    }
}
