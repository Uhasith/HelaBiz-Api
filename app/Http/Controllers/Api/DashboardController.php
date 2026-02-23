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
        $user = Auth::user();
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
