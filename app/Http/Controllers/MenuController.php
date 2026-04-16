<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\StoreMenuItemRequest;
use App\Http\Requests\Menu\UpdateMenuItemRequest;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $companyId = $user->company_id;
        $branchId  = $user->effectiveBranchId();

        $categories = Category::where('company_id', $companyId)
            ->forBranch($branchId ?? 0)
            ->withCount('menuItems')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Menu/Index', ['categories' => $categories]);
    }

    public function getItems(Request $request): JsonResponse
    {
        $items = MenuItem::with(['category', 'variants', 'taxGroup', 'recipe'])
            ->where('company_id', $request->user()->company_id)
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->paginate(25);

        return response()->json($items);
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'company_id' => $request->user()->company_id,
            'slug'       => Str::slug($request->name) . '-' . Str::random(4),
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu', 'public');
        }

        $item = MenuItem::create($data);

        // Attach modifier groups
        if ($request->modifier_group_ids) {
            $item->modifierGroups()->sync($request->modifier_group_ids);
        }

        return response()->json(['item' => $item->load(['category', 'variants', 'modifierGroups']), 'message' => 'Menu item created.'], 201);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu', 'public');
        }

        $menuItem->update($data);

        if ($request->has('modifier_group_ids')) {
            $menuItem->modifierGroups()->sync($request->modifier_group_ids);
        }

        return response()->json(['item' => $menuItem->fresh(['category', 'variants', 'modifierGroups']), 'message' => 'Menu item updated.']);
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();
        return response()->json(['message' => 'Menu item deleted.']);
    }

    public function toggleAvailability(MenuItem $menuItem): JsonResponse
    {
        $menuItem->update(['is_available' => ! $menuItem->is_available]);
        return response()->json(['is_available' => $menuItem->is_available]);
    }

    // ── Categories ───────────────────────────────────────────────────────

    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'color'     => 'nullable|string|max:7',
            'sort_order'=> 'nullable|integer',
        ]);

        $category = Category::create([
            'company_id' => $request->user()->company_id,
            'branch_id'  => $request->user()->branch_id,
            'name'       => $request->name,
            'slug'       => Str::slug($request->name) . '-' . Str::random(4),
            'parent_id'  => $request->parent_id,
            'color'      => $request->color,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json(['category' => $category, 'message' => 'Category created.'], 201);
    }

    // ── Modifier Groups ──────────────────────────────────────────────────

    public function getModifierGroups(Request $request): JsonResponse
    {
        $groups = ModifierGroup::with('modifiers')
            ->where('company_id', $request->user()->company_id)
            ->get();

        return response()->json($groups);
    }
}
