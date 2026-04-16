<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $companyId = $user->company_id;
        $branchId  = $user->branch_id;

        $settings = Setting::where(fn ($q) =>
                $q->where('company_id', $companyId)->where('branch_id', null)
                  ->orWhere('branch_id', $branchId)
            )
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        $taxRates = TaxRate::where('company_id', $companyId)->get();

        $branches = Branch::where('company_id', $companyId)->get(['id', 'name', 'phone', 'address', 'is_active']);

        return Inertia::render('Settings/Index', compact('settings', 'taxRates', 'branches'));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $user = $request->user();

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['company_id' => $user->company_id, 'key' => $key],
                ['value' => $value, 'branch_id' => $request->branch_specific ? $user->branch_id : null]
            );
        }

        return response()->json(['message' => 'Settings saved.']);
    }

    public function storeTaxRate(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'rate'       => 'required|numeric|min:0|max:100',
            'type'       => 'required|in:vat,service_charge,other',
            'is_default' => 'boolean',
            'is_inclusive' => 'boolean',
        ]);

        // If marking as default, unset others
        if ($request->is_default) {
            TaxRate::where('company_id', $request->user()->company_id)
                ->where('type', $request->type)
                ->update(['is_default' => false]);
        }

        $tax = TaxRate::create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return response()->json(['tax_rate' => $tax, 'message' => 'Tax rate created.'], 201);
    }

    public function updateTaxRate(Request $request, TaxRate $taxRate): JsonResponse
    {
        $request->validate([
            'name'       => 'string|max:100',
            'rate'       => 'numeric|min:0|max:100',
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ]);

        $taxRate->update($request->validated());
        return response()->json(['tax_rate' => $taxRate, 'message' => 'Updated.']);
    }

    public function storeBranch(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city'    => 'nullable|string|max:100',
        ]);

        $branch = Branch::create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return response()->json(['branch' => $branch, 'message' => 'Branch created.'], 201);
    }
}
