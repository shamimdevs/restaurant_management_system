<?php

namespace App\Http\Controllers;

use App\Models\FloorPlan;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId  = $request->user()->branch_id;
        $floorPlans = FloorPlan::where('branch_id', $branchId)
            ->with(['tables' => fn ($q) => $q->where('is_active', true)->with('activeSession')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Tables/Index', ['floorPlans' => $floorPlans]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'table_number' => 'required|string|max:20',
            'name'         => 'nullable|string|max:100',
            'capacity'     => 'required|integer|min:1|max:50',
            'floor_plan_id'=> 'nullable|exists:floor_plans,id',
            'shape'        => 'in:square,round,rectangle',
        ]);

        $table = RestaurantTable::create([
            'branch_id'    => $request->user()->branch_id,
            'floor_plan_id'=> $request->floor_plan_id,
            'table_number' => $request->table_number,
            'name'         => $request->name,
            'capacity'     => $request->capacity,
            'shape'        => $request->shape ?? 'square',
            'qr_code'      => Str::uuid()->toString(),
        ]);

        // Generate QR image
        $this->generateQrImage($table);

        return response()->json(['table' => $table, 'message' => 'Table created.'], 201);
    }

    public function update(Request $request, RestaurantTable $table): JsonResponse
    {
        $request->validate([
            'table_number' => 'string|max:20',
            'capacity'     => 'integer|min:1|max:50',
            'status'       => 'in:available,occupied,reserved,maintenance',
        ]);

        $table->update($request->only('table_number', 'name', 'capacity', 'status', 'floor_plan_id', 'shape'));

        return response()->json(['table' => $table->fresh()]);
    }

    public function destroy(RestaurantTable $table): JsonResponse
    {
        if ($table->activeSession) {
            return response()->json(['message' => 'Cannot delete an occupied table.'], 422);
        }
        $table->delete();
        return response()->json(['message' => 'Table deleted.']);
    }

    public function regenerateQr(RestaurantTable $table): JsonResponse
    {
        $table->update(['qr_code' => Str::uuid()->toString()]);
        $this->generateQrImage($table);
        return response()->json(['qr_code' => $table->qr_code, 'qr_url' => $table->getQrUrl()]);
    }

    // ── Table Sessions ───────────────────────────────────────────────────

    public function openSession(Request $request, RestaurantTable $table): JsonResponse
    {
        if (! $table->isAvailable()) {
            return response()->json(['message' => 'Table is not available.'], 422);
        }

        $session = TableSession::create([
            'branch_id'           => $table->branch_id,
            'restaurant_table_id' => $table->id,
            'waiter_id'           => $request->user()->id,
            'customer_id'         => $request->customer_id ?? null,
            'pax'                 => $request->pax ?? 1,
            'started_at'          => now(),
        ]);

        $table->markOccupied();

        return response()->json(['session' => $session->load('table', 'waiter'), 'message' => 'Table session opened.'], 201);
    }

    public function closeSession(TableSession $session): JsonResponse
    {
        $session->close();
        return response()->json(['message' => 'Session closed. Table is now available.']);
    }

    // ── QR Order Page (public) ───────────────────────────────────────────

    public function qrOrderPage(string $code): Response
    {
        $table = RestaurantTable::where('qr_code', $code)
                                ->where('is_active', true)
                                ->with('branch.company')
                                ->firstOrFail();

        $categories = \App\Models\Category::active()
            ->forBranch($table->branch_id)
            ->with(['menuItems' => fn ($q) => $q->available()])
            ->get();

        return Inertia::render('QrOrder/Menu', [
            'table'      => $table,
            'branch'     => $table->branch,
            'categories' => $categories,
        ]);
    }

    // ── Private ──────────────────────────────────────────────────────────

    private function generateQrImage(RestaurantTable $table): void
    {
        // Requires: composer require SimpleSoftwareIO/simple-qrcode
        // Generates QR image and stores it in public/qr-codes/
        $url  = route('qr.order', ['code' => $table->qr_code]);
        $path = "qr-codes/table-{$table->id}.svg";

        // QrCode::size(300)->generate($url, public_path($path));
        $table->update(['qr_image_path' => $path]);
    }
}
