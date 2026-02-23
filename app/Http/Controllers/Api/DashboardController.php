<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user()->load('tenant');
        $tenantId = $user->tenant_id;

        // Get counts
        $totalProducts = Product::query()->where('tenant_id', $tenantId)->count();
        $totalCustomers = Customer::query()->where('tenant_id', $tenantId)->count();
        $totalOrders = Order::query()->where('tenant_id', $tenantId)->count();
        $totalInvoices = Invoice::query()->where('tenant_id', $tenantId)->count();
        $totalQuotations = Quotation::query()->where('tenant_id', $tenantId)->count();

        // Get revenue statistics
        $totalRevenue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->sum('total');

        $pendingOrdersValue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->sum('total');

        // Get low stock products
        $lowStockProducts = Product::query()
            ->where('tenant_id', $tenantId)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->orderBy('stock_quantity', 'asc')
            ->limit(5)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_alert']);

        // Get recent orders
        $recentOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->with(['customer:id,name', 'items.product:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent invoices
        $recentInvoices = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->with(['customer:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        // Get pending quotations
        $pendingQuotations = Quotation::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->with(['customer:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture_url' => $user->getFirstMediaUrl('profile_picture'),
                'profile_picture_thumb_url' => $user->getFirstMediaUrl('profile_picture', 'thumb'),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'tenant' => [
                    'id' => $user->tenant->id,
                    'business_name' => $user->tenant->business_name,
                    'logo_url' => $user->tenant->getFirstMediaUrl('logo'),
                    'logo_thumb_url' => $user->tenant->getFirstMediaUrl('logo', 'thumb'),
                    'phone' => $user->tenant->phone,
                    'email' => $user->tenant->email,
                    'address' => $user->tenant->address,
                    'city' => $user->tenant->city,
                    'country' => $user->tenant->country,
                    'currency' => $user->tenant->currency,
                    'created_at' => $user->tenant->created_at,
                    'updated_at' => $user->tenant->updated_at,
                ],
            ],
            'counts' => [
                'products' => $totalProducts,
                'customers' => $totalCustomers,
                'orders' => $totalOrders,
                'invoices' => $totalInvoices,
                'quotations' => $totalQuotations,
            ],
            'revenue' => [
                'total' => (float) $totalRevenue,
                'pending_orders' => (float) $pendingOrdersValue,
            ],
            'low_stock_products' => $lowStockProducts,
            'recent_orders' => $recentOrders,
            'recent_invoices' => $recentInvoices,
            'pending_quotations' => $pendingQuotations,
        ]);
    }
}
