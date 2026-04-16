<?php

namespace App\Http\Controllers;

use App\Models\KitchenTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenController extends Controller
{
    /**
     * Kitchen Display System screen.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Kitchen/Display', [
            'branch_id' => $request->user()->branch_id,
        ]);
    }

    /**
     * Get active tickets for a branch (polling endpoint).
     */
    public function getTickets(Request $request): JsonResponse
    {
        $tickets = KitchenTicket::active()
            ->where('branch_id', $request->user()->branch_id)
            ->with(['items', 'order:id,order_number,order_type,notes'])
            ->orderBy('priority')
            ->orderBy('created_at')
            ->get();

        return response()->json($tickets);
    }

    /**
     * Start cooking a ticket.
     */
    public function startCooking(KitchenTicket $ticket): JsonResponse
    {
        $ticket->startCooking();
        return response()->json(['ticket' => $ticket->fresh('items'), 'message' => 'Cooking started.']);
    }

    /**
     * Mark ticket as ready.
     */
    public function markReady(KitchenTicket $ticket): JsonResponse
    {
        $ticket->markReady();
        return response()->json(['ticket' => $ticket->fresh('items'), 'message' => 'Order is ready.']);
    }

    /**
     * Mark ticket as served / bump from screen.
     */
    public function markServed(KitchenTicket $ticket): JsonResponse
    {
        $ticket->markServed();
        $ticket->increment('bump_count');
        return response()->json(['message' => 'Ticket bumped.']);
    }

    /**
     * Update individual item status within a ticket.
     */
    public function updateItemStatus(Request $request, KitchenTicket $ticket, int $itemId): JsonResponse
    {
        $request->validate(['status' => 'required|in:pending,cooking,ready,cancelled']);

        $item = $ticket->items()->findOrFail($itemId);
        $item->update([
            'status'       => $request->status,
            'started_at'   => $request->status === 'cooking' ? now() : $item->started_at,
            'completed_at' => $request->status === 'ready'   ? now() : $item->completed_at,
        ]);

        // If all items are ready, auto-mark ticket as ready
        if ($ticket->items()->where('status', '!=', 'ready')->where('status', '!=', 'cancelled')->doesntExist()) {
            $ticket->markReady();
        }

        return response()->json(['item' => $item]);
    }
}
