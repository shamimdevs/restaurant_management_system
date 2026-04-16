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
        $branchId = $request->user()->branch_id;
        $summary  = $this->inventoryService->getStockSummary($branchId);

        return Inertia::render('Inventory/Index', ['summary' => $summary]);
    }

    // ── Ingredients ──────────────────────────────────────────────────────

    public function getIngredients(Request $request): JsonResponse
    {
        $ingredients = Ingredient::with('unit')
            ->where('branch_id', $request->user()->branch_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->low_stock, fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(25);

        return response()->json($ingredients);
    }

    public function storeIngredient(StoreIngredientRequest $request): JsonResponse
    {
        $ingredient = Ingredient::create(array_merge($request->validated(), [
            'company_id' => $request->user()->company_id,
            'branch_id'  => $request->user()->branch_id,
        ]));

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
        $request->validate([
            'reason'             => 'required|string',
            'items'              => 'required|array|min:1',
            'items.*.ingredient_id'  => 'required|exists:ingredients,id',
            'items.*.actual_quantity'=> 'required|numeric|min:0',
        ]);

        $user = $request->user();

        $adj = InventoryAdjustment::create([
            'branch_id'    => $user->branch_id,
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
            ->forBranch($request->user()->branch_id)
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
            ->where('branch_id', $request->user()->branch_id)
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
