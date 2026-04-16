<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\StoreIngredientRequest;
use App\Models\Ingredient;
use App\Models\InventoryAdjustment;
use App\Models\Recipe;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): Response
    {
        $branchId = $request->user()->effectiveBranchId();
        $summary  = $this->inventoryService->getStockSummary($branchId);

        return Inertia::render('Inventory/Index', ['summary' => $summary]);
    }

    // ── Ingredients ──────────────────────────────────────────────────────

    public function getIngredients(Request $request): JsonResponse
    {
        $branchId    = $request->user()->effectiveBranchId();
        $ingredients = Ingredient::with('unit')
            ->where('branch_id', $branchId)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->low_stock, fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(25);

        return response()->json($ingredients);
    }

    public function storeIngredient(Request $request): JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:150',
            'unit'            => 'nullable|string|max:50',
            'cost_per_unit'   => 'nullable|numeric|min:0',
            'current_stock'   => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'reorder_level'   => 'nullable|numeric|min:0',
        ]);

        $user     = $request->user();
        $branchId = $user->effectiveBranchId();

        // Resolve unit_id from free-text unit string
        $unitId = null;
        if ($request->filled('unit')) {
            $unit   = \App\Models\Unit::whereRaw('LOWER(abbreviation) = ?', [strtolower($request->unit)])
                        ->orWhereRaw('LOWER(name) = ?', [strtolower($request->unit)])
                        ->first();
            $unitId = $unit?->id;
        }
        if (! $unitId) {
            $unitId = \App\Models\Unit::first()?->id ?? 6; // fallback to Piece
        }

        $ingredient = Ingredient::create([
            'company_id'      => $user->company_id,
            'branch_id'       => $branchId,
            'unit_id'         => $unitId,
            'name'            => $request->name,
            'cost_per_unit'   => $request->cost_per_unit ?? 0,
            'current_stock'   => $request->current_stock ?? 0,
            'min_stock_level' => $request->min_stock_level ?? 0,
            'reorder_point'   => $request->reorder_level ?? $request->reorder_point ?? 0,
            'track_stock'     => true,
            'is_active'       => true,
        ]);

        return response()->json(['ingredient' => $ingredient->load('unit'), 'message' => 'Ingredient created.'], 201);
    }

    public function updateIngredient(Request $request, Ingredient $ingredient): JsonResponse
    {
        $request->validate([
            'name'            => 'string|max:150',
            'cost_per_unit'   => 'numeric|min:0',
            'min_stock_level' => 'numeric|min:0',
            'reorder_point'   => 'nullable|numeric',
        ]);

        $ingredient->update($request->only('name', 'description', 'cost_per_unit', 'min_stock_level', 'max_stock_level', 'reorder_point', 'storage_location', 'is_active'));

        return response()->json(['ingredient' => $ingredient->fresh(), 'message' => 'Ingredient updated.']);
    }

    // ── Recipes ──────────────────────────────────────────────────────────

    public function getRecipes(Request $request): JsonResponse
    {
        $recipes = Recipe::with(['menuItem:id,name', 'ingredients.ingredient', 'ingredients.unit'])
            ->whereHas('menuItem', fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->paginate(20);

        return response()->json($recipes);
    }

    public function storeRecipe(Request $request): JsonResponse
    {
        $request->validate([
            'menu_item_id'              => 'required|exists:menu_items,id',
            'variant_id'                => 'nullable|exists:menu_item_variants,id',
            'yield_quantity'            => 'required|numeric|min:0.01',
            'yield_unit_id'             => 'required|exists:units,id',
            'ingredients'               => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.unit_id'     => 'required|exists:units,id',
            'ingredients.*.quantity'    => 'required|numeric|min:0.0001',
            'ingredients.*.waste_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $recipe = Recipe::updateOrCreate(
            ['menu_item_id' => $request->menu_item_id, 'variant_id' => $request->variant_id],
            $request->only('yield_quantity', 'yield_unit_id', 'instructions', 'prep_time_minutes', 'cook_time_minutes')
        );

        $recipe->ingredients()->delete();
        foreach ($request->ingredients as $ing) {
            $recipe->ingredients()->create($ing);
        }

        return response()->json(['recipe' => $recipe->load('ingredients.ingredient'), 'message' => 'Recipe saved.']);
    }

    // ── Stock Adjustments ────────────────────────────────────────────────

    public function createAdjustment(Request $request): JsonResponse
    {
        $user     = $request->user();
        $branchId = $user->effectiveBranchId();

        // ── Simple single-ingredient delta format (from POS/Inventory frontend) ──
        if ($request->has('ingredient_id')) {
            $request->validate([
                'ingredient_id' => 'required|integer|exists:ingredients,id',
                'quantity'      => 'required|numeric',
                'reason'        => 'required|string',
                'notes'         => 'nullable|string|max:500',
            ]);

            $ingredient = Ingredient::findOrFail($request->ingredient_id);
            $delta      = (float) $request->quantity;
            $before     = (float) $ingredient->current_stock;
            $after      = max(0, $before + $delta);

            $ingredient->update(['current_stock' => $after]);

            StockMovement::create([
                'branch_id'      => $branchId,
                'ingredient_id'  => $ingredient->id,
                'unit_id'        => $ingredient->unit_id,
                'type'           => $delta >= 0 ? 'adjustment_in' : 'adjustment_out',
                'quantity'       => abs($delta),
                'unit_cost'      => $ingredient->cost_per_unit,
                'total_cost'     => round(abs($delta) * $ingredient->cost_per_unit, 2),
                'stock_before'   => $before,
                'stock_after'    => $after,
                'reference_type' => 'manual_adjustment',
                'user_id'        => $user->id,
                'notes'          => $request->reason . ($request->notes ? ': ' . $request->notes : ''),
                'movement_date'  => today(),
            ]);

            return response()->json([
                'ingredient' => $ingredient->fresh('unit'),
                'message'    => 'Stock adjusted successfully.',
            ]);
        }

        // ── Bulk adjustment format (items array with actual_quantity) ──
        $request->validate([
            'reason'                  => 'required|string',
            'items'                   => 'required|array|min:1',
            'items.*.ingredient_id'   => 'required|exists:ingredients,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
        ]);

        $adj = InventoryAdjustment::create([
            'branch_id'    => $branchId,
            'reference_no' => 'ADJ-' . now()->format('YmdHis'),
            'status'       => 'pending',
            'reason'       => $request->reason,
            'created_by'   => $user->id,
        ]);

        foreach ($request->items as $item) {
            $ingredient = Ingredient::find($item['ingredient_id']);
            $adj->items()->create([
                'ingredient_id'   => $ingredient->id,
                'system_quantity' => $ingredient->current_stock,
                'actual_quantity' => $item['actual_quantity'],
                'difference'      => $item['actual_quantity'] - $ingredient->current_stock,
                'notes'           => $item['notes'] ?? null,
            ]);
        }

        return response()->json(['adjustment' => $adj->load('items.ingredient'), 'message' => 'Adjustment submitted for approval.'], 201);
    }

    public function approveAdjustment(InventoryAdjustment $adjustment): JsonResponse
    {
        $this->inventoryService->applyAdjustment($adjustment, request()->user()->id);
        return response()->json(['message' => 'Adjustment approved and applied.']);
    }

    // ── Stock Movements ──────────────────────────────────────────────────

    public function getMovements(Request $request): JsonResponse
    {
        $movements = StockMovement::with(['ingredient:id,name', 'unit:id,abbreviation'])
            ->forBranch($request->user()->effectiveBranchId())
            ->when($request->ingredient_id, fn ($q) => $q->where('ingredient_id', $request->ingredient_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->from, fn ($q) => $q->dateRange($request->from, $request->to ?? today()->toDateString()))
            ->latest()
            ->paginate(50);

        return response()->json($movements);
    }

    // ── Alerts ───────────────────────────────────────────────────────────

    public function getAlerts(Request $request): JsonResponse
    {
        $alerts = StockAlert::unresolved()
            ->where('branch_id', $request->user()->effectiveBranchId())
            ->with('ingredient:id,name,current_stock,min_stock_level,unit_id')
            ->latest()
            ->get();

        return response()->json($alerts);
    }

    public function resolveAlert(StockAlert $alert): JsonResponse
    {
        $alert->resolve(request()->user()->id);
        return response()->json(['message' => 'Alert resolved.']);
    }
}
