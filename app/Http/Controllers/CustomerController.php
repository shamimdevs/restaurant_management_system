<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerFeedback;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Customers/Index');
    }

    public function list(Request $request): JsonResponse
    {
        $customers = Customer::withCount(['orders' => fn ($q) => $q->where('status', 'completed')])
            ->where('company_id', $request->user()->company_id)
            ->when($request->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            ))
            ->when($request->segment, fn ($q) => $q->where('segment', $request->segment))
            ->orderByDesc('total_spent')
            ->paginate(25);

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
        ]);

        $customer = Customer::updateOrCreate(
            ['company_id' => $request->user()->company_id, 'phone' => $request->phone],
            array_merge($request->only('name', 'email', 'gender', 'date_of_birth', 'address', 'area', 'city'), [
                'company_id' => $request->user()->company_id,
            ])
        );

        return response()->json(['customer' => $customer, 'message' => 'Customer saved.'], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['addresses', 'pointTransactions' => fn ($q) => $q->latest()->limit(10)]);

        $orderHistory = Order::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->with('items:id,order_id,item_name,quantity,total')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'customer'      => $customer,
            'order_history' => $orderHistory,
            'stats' => [
                'total_orders'  => $orderHistory->count(),
                'total_spent'   => $customer->total_spent,
                'loyalty_points'=> $customer->loyalty_points,
                'avg_order'     => $orderHistory->count()
                    ? round($customer->total_spent / $orderHistory->count(), 2) : 0,
            ],
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $customer->update($request->only('name', 'email', 'gender', 'date_of_birth', 'anniversary_date', 'address', 'area', 'city', 'notes'));
        return response()->json(['customer' => $customer->fresh(), 'message' => 'Customer updated.']);
    }

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $base = Customer::where('company_id', $companyId);

        return response()->json([
            'total'      => $base->count(),
            'vip'        => (clone $base)->where('segment', 'vip')->count(),
            'active'     => (clone $base)->where('last_visit_at', '>=', now()->subDays(30))->count(),
            'with_points'=> (clone $base)->where('loyalty_points', '>', 0)->count(),
        ]);
    }

    public function searchByPhone(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string']);

        $customer = Customer::where('company_id', $request->user()->company_id)
                            ->byPhone($request->phone)
                            ->first();

        return response()->json($customer);
    }

    public function storeFeedback(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'order_id'       => 'required|exists:orders,id',
            'overall_rating' => 'required|integer|min:1|max:5',
            'food_rating'    => 'nullable|integer|min:1|max:5',
            'service_rating' => 'nullable|integer|min:1|max:5',
            'ambiance_rating'=> 'nullable|integer|min:1|max:5',
            'comment'        => 'nullable|string|max:1000',
        ]);

        $feedback = CustomerFeedback::create(array_merge($request->validated(), [
            'customer_id' => $customer->id,
            'branch_id'   => $request->user()->branch_id,
        ]));

        return response()->json(['feedback' => $feedback, 'message' => 'Thank you for your feedback!'], 201);
    }
}
