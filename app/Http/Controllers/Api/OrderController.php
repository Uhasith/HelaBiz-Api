<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Date range filters
        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('order_date', '>=', $fromDate);
        }

        if ($toDate = $request->input('to_date')) {
            $query->whereDate('order_date', '<=', $toDate);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'order_date', 'total', 'order_number'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('created_at');
        }

        $perPage = min($request->input('per_page', 15), 100);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('OrderController@store called', ['request' => $request->all()]);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20|required_without:customer_email',
            'customer_email' => 'nullable|email|max:255|required_without:customer_phone',
            'address' => 'nullable|string|max:500',
            'order_date' => 'required|date',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'warranty_period' => 'nullable|integer|min:1',
            'warranty_unit' => 'nullable|in:days,weeks,months,years|required_with:warranty_period',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            // Find or create customer
            $customer = $this->findOrCreateCustomer($validated, $request->user()->tenant_id);

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $order = Order::create([
                'tenant_id' => $request->user()->tenant_id,
                'customer_id' => $customer->id,
                'order_number' => $orderNumber,
                'order_date' => $validated['order_date'],
                'status' => $validated['status'],
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'],
                'discount' => $validated['discount'],
                'total' => $validated['total'],
                'warranty_period' => $validated['warranty_period'] ?? null,
                'warranty_unit' => $validated['warranty_unit'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items with product snapshots
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
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

        Log::info('OrderController@update called', ['order_id' => $order->id, 'request' => $request->all()]);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20|required_without:customer_email',
            'customer_email' => 'nullable|email|max:255|required_without:customer_phone',
            'address' => 'nullable|string|max:500',
            'order_date' => 'required|date',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'warranty_period' => 'nullable|integer|min:1',
            'warranty_unit' => 'nullable|in:days,weeks,months,years|required_with:warranty_period',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:order_items,id',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $order, $request) {
            // Find or create customer
            $customer = $this->findOrCreateCustomer($validated, $request->user()->tenant_id);

            // Update order
            $order->update([
                'customer_id' => $customer->id,
                'order_date' => $validated['order_date'],
                'status' => $validated['status'],
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'],
                'discount' => $validated['discount'],
                'total' => $validated['total'],
                'warranty_period' => $validated['warranty_period'] ?? null,
                'warranty_unit' => $validated['warranty_unit'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Get existing item IDs from request
            $requestItemIds = collect($validated['items'])
                ->filter(fn ($item) => isset($item['id']))
                ->pluck('id')
                ->toArray();

            // Delete items not in the request
            $order->items()->whereNotIn('id', $requestItemIds)->delete();

            // Update or create items
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                $itemData = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ];

                if (isset($item['id'])) {
                    // Update existing item
                    $order->items()->where('id', $item['id'])->update($itemData);
                } else {
                    // Create new item
                    $order->items()->create($itemData);
                }
            }

            $order->load(['customer', 'items.product']);
        });

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        // Optional: Restrict deletion of completed orders
        if ($order->status === 'completed') {
            return response()->json([
                'message' => 'Cannot delete completed orders',
            ], 403);
        }

        $order->delete();

        return response()->json(null, 204);
    }

    /**
     * Update only the order status.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'updated_at' => $order->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Find or create a customer based on phone or email.
     */
    protected function findOrCreateCustomer(array $data, int $tenantId): Customer
    {
        // Try to find existing customer by phone or email
        $customer = Customer::where('tenant_id', $tenantId)
            ->where(function ($query) use ($data) {
                if (! empty($data['customer_phone'])) {
                    $query->where('phone', $data['customer_phone']);
                }
                if (! empty($data['customer_email'])) {
                    $query->orWhere('email', $data['customer_email']);
                }
            })
            ->first();

        // If customer doesn't exist, create a new one
        if (! $customer) {
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? null,
                'email' => $data['customer_email'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
        } else {
            // Update customer info if provided
            $customer->update([
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? $customer->phone,
                'email' => $data['customer_email'] ?? $customer->email,
                'address' => $data['address'] ?? $customer->address,
            ]);
        }

        return $customer;
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = Order::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? ((int) substr($lastOrder->order_number, -6)) + 1 : 1;

        return sprintf('ORD-%s-%06d', $year, $sequence);
    }
}
