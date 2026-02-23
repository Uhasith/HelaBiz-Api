<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with(['customer', 'items.product']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest('order_date')
            ->paginate($request->input('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            // Generate order number
            $orderNumber = 'ORD-'.date('Ymd').'-'.str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'tenant_id' => $request->user()->tenant_id,
                'customer_id' => $validated['customer_id'],
                'order_number' => $orderNumber,
                'order_date' => $validated['order_date'],
                'status' => $validated['status'],
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'total' => $validated['total'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items
            foreach ($validated['items'] as $item) {
                $order->items()->create($item);
            }

            return $order->load(['customer', 'items.product']);
        });

        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        return response()->json($order->load(['customer', 'items.product']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $order->update([
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
                'status' => $validated['status'],
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'total' => $validated['total'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing items and create new ones
            $order->items()->delete();
            foreach ($validated['items'] as $item) {
                $order->items()->create($item);
            }
        });

        return response()->json($order->load(['customer', 'items.product']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        $order->delete();

        return response()->json(null, 204);
    }
}
