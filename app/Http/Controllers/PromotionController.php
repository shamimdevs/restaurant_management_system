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
            'name'           => 'required|string|max:150',
            'type'           => 'required|in:percentage,fixed,bxgy,free_item',
            'discount_value' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'applicable_days'=> 'nullable|array',
            'is_active'      => 'boolean',
        ]);

        $promotion = Promotion::create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
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
            'code'              => 'required|string|max:50',
            'discount_type'     => 'required|in:percentage,fixed',
            'discount_value'    => 'required|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'max_discount_amount'=> 'nullable|numeric|min:0',
            'usage_limit'       => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'expires_at'        => 'nullable|date|after:now',
            'is_active'         => 'boolean',
        ]);

        // Check uniqueness within company
        $exists = Coupon::where('company_id', $request->user()->company_id)
            ->where('code', strtoupper($request->code))
            ->exists();

        if ($exists) {
            return response()->json(['errors' => ['code' => ['Coupon code already exists.']]], 422);
        }

        $coupon = Coupon::create([
            ...$request->validated(),
            'code'       => strtoupper($request->code),
            'company_id' => $request->user()->company_id,
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
            $request->validated()
        );

        return response()->json(['loyalty' => $loyalty, 'message' => 'Loyalty settings saved.']);
    }

    // ── Generate coupon code ──────────────────────────────────────────────

    public function generateCode(): JsonResponse
    {
        return response()->json(['code' => strtoupper(Str::random(8))]);
    }
}
