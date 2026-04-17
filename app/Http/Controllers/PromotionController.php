<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\LoyaltyProgram;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId  = $request->user()->company_id;
        $promotions = Promotion::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get();

        $coupons = Coupon::where('company_id', $companyId)
            ->withCount('usages')
            ->orderByDesc('created_at')
            ->get();

        $loyalty = LoyaltyProgram::where('company_id', $companyId)->first();

        return Inertia::render('Promotions/Index', compact('promotions', 'coupons', 'loyalty'));
    }

    // ── Promotions CRUD ───────────────────────────────────────────────────

    public function storePromotion(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'type'             => 'required|in:percentage,fixed,bxgy,free_item,percentage_discount,fixed_discount,buy_x_get_y,happy_hour,combo',
            'value'            => 'nullable|numeric|min:0',
            'discount_value'   => 'nullable|numeric|min:0',
            'min_order_value'  => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'applicable_days'  => 'nullable|array',
            'is_active'        => 'boolean',
        ]);

        // Map frontend short type names to DB enum values
        $typeMap = [
            'percentage' => 'percentage_discount',
            'fixed'      => 'fixed_discount',
            'bxgy'       => 'buy_x_get_y',
        ];
        $dbType = $typeMap[$request->type] ?? $request->type;

        $promotion = Promotion::create([
            'company_id'      => $request->user()->company_id,
            'name'            => $request->name,
            'type'            => $dbType,
            'value'           => $request->value ?? $request->discount_value ?? 0,
            'min_order_value' => $request->min_order_value ?? $request->min_order_amount ?? 0,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'days_of_week'    => $request->applicable_days,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        return response()->json(['promotion' => $promotion, 'message' => 'Promotion created.'], 201);
    }

    public function updatePromotion(Request $request, Promotion $promotion): JsonResponse
    {
        $request->validate([
            'name'       => 'string|max:150',
            'is_active'  => 'boolean',
            'end_date'   => 'date',
        ]);

        $promotion->update($request->validated());
        return response()->json(['promotion' => $promotion, 'message' => 'Updated.']);
    }

    public function togglePromotion(Promotion $promotion): JsonResponse
    {
        $promotion->update(['is_active' => !$promotion->is_active]);
        return response()->json(['is_active' => $promotion->is_active]);
    }

    // ── Coupons ───────────────────────────────────────────────────────────

    public function getCoupons(Request $request): JsonResponse
    {
        $coupons = Coupon::where('company_id', $request->user()->company_id)
            ->withCount('usages')
            ->get();

        return response()->json($coupons);
    }

    public function storeCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code'                  => 'required|string|max:50',
            'discount_type'         => 'required|in:percentage,fixed',
            'discount_value'        => 'required|numeric|min:0',
            'min_order_amount'      => 'nullable|numeric|min:0',
            'max_discount_amount'   => 'nullable|numeric|min:0',
            'usage_limit'           => 'nullable|integer|min:1',
            'usage_limit_per_user'  => 'nullable|integer|min:1',
            'expires_at'            => 'nullable|date',
            'is_active'             => 'boolean',
        ]);

        $exists = Coupon::where('company_id', $request->user()->company_id)
            ->where('code', strtoupper($request->code))
            ->exists();

        if ($exists) {
            return response()->json(['errors' => ['code' => ['Coupon code already exists.']]], 422);
        }

        $coupon = Coupon::create([
            'company_id'              => $request->user()->company_id,
            'code'                    => strtoupper($request->code),
            'discount_type'           => $request->discount_type,
            'discount_value'          => $request->discount_value,
            'min_order_value'         => $request->min_order_amount ?? 0,
            'max_discount'            => $request->max_discount_amount ?: null,
            'usage_limit'             => $request->usage_limit ?: null,
            'usage_limit_per_customer'=> $request->usage_limit_per_user ?? 1,
            'valid_from'              => now(),
            'valid_until'             => $request->expires_at ?: null,
            'is_active'               => $request->boolean('is_active', true),
        ]);

        return response()->json(['coupon' => $coupon, 'message' => 'Coupon created.'], 201);
    }

    public function toggleCoupon(Coupon $coupon): JsonResponse
    {
        $coupon->update(['is_active' => !$coupon->is_active]);
        return response()->json(['is_active' => $coupon->is_active]);
    }

    // ── Loyalty ───────────────────────────────────────────────────────────

    public function updateLoyalty(Request $request): JsonResponse
    {
        $request->validate([
            'points_per_taka'    => 'required|numeric|min:0',
            'taka_per_point'     => 'required|numeric|min:0',
            'min_redeem_points'  => 'required|integer|min:1',
            'expiry_days'        => 'nullable|integer|min:1',
            'is_active'          => 'boolean',
        ]);

        $loyalty = LoyaltyProgram::updateOrCreate(
            ['company_id' => $request->user()->company_id],
            [
                'name'                => 'Default Loyalty Program',
                'points_per_currency' => $request->points_per_taka,
                'currency_per_point'  => $request->taka_per_point,
                'min_redeem_points'   => $request->min_redeem_points,
                'redeem_value'        => $request->taka_per_point,
                'min_order_for_earn'  => 0,
                'point_expiry_days'   => $request->expiry_days,
                'is_active'           => $request->boolean('is_active', true),
            ]
        );

        return response()->json(['loyalty' => $loyalty, 'message' => 'Loyalty settings saved.']);
    }

    // ── Generate coupon code ──────────────────────────────────────────────

    public function generateCode(): JsonResponse
    {
        return response()->json(['code' => strtoupper(Str::random(8))]);
    }
}
