<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('tenant_id', $request->user()->tenant_id);

        Log::info('ProductController@index called', ['request' => $request->all()]);

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()
            ->paginate($request->input('per_page', 5));

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'photo' => 'nullable|image|mimes:jpeg,png|max:5120',
        ]);

        $product = Product::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
        ]);

        if ($request->hasFile('photo')) {
            $product->addMediaFromRequest('photo')
                ->toMediaCollection('product_image');
        }

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'photo' => 'nullable|image|mimes:jpeg,png|max:5120',
            'remove_photo' => 'nullable|boolean',
        ]);

        $product->update($validated);

        if ($request->boolean('remove_photo')) {
            $product->clearMediaCollection('product_image');
        }

        if ($request->hasFile('photo')) {
            $product->clearMediaCollection('product_image');
            $product->addMediaFromRequest('photo')
                ->toMediaCollection('product_image');
        }

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        if ($product->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Unauthorized');
        }

        $product->delete();

        return response()->json(null, 204);
    }
}
