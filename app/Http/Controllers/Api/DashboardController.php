<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * List low stock products (paginated)
     */
    public function lowStockProducts(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $perPage = min($request->input('per_page', 10), 50);

        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->orderBy('stock_quantity', 'asc')
            ->paginate($perPage, ['id', 'name', 'sku', 'stock_quantity', 'low_stock_alert']);

        return response()->json($products);
    }

    /**
     * List unpaid invoices (paginated)
     */
    public function unpaidInvoices(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $perPage = min($request->input('per_page', 10), 50);

        $invoices = Invoice::query()
            ->with('customer')
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['id', 'invoice_number', 'customer_id', 'total', 'status', 'created_at', 'order_id']);

        return response()->json($invoices);
    }

    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user()->load('tenant');
        $tenantId = $user->tenant_id;

        // Get low stock products count
        $lowStockProductsCount = Product::query()
            ->where('tenant_id', $tenantId)
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->count();

        // Get unpaid invoices count
        $unpaidInvoicesCount = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->count();

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
            'low_stock_products_count' => $lowStockProductsCount,
            'unpaid_invoices_count' => $unpaidInvoicesCount,
        ]);
    }

    /**
     * Get revenue statistics
     */
    public function revenue(): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Get revenue statistics
        $totalRevenue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->sum('total');

        $pendingOrdersValue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->sum('total');

        $todayRevenue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('total');

        $thisWeekRevenue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total');

        $thisMonthRevenue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $lastMonthRevenue = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total');

        // Calculate growth percentage (this month vs last month)
        $growthPercentage = 0;
        if ($lastMonthRevenue > 0) {
            $growthPercentage = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        } elseif ($thisMonthRevenue > 0) {
            $growthPercentage = 100;
        }

        return response()->json([
            'revenue' => [
                'total' => (float) $totalRevenue,
                'pending_orders' => (float) $pendingOrdersValue,
                'today' => (float) $todayRevenue,
                'this_week' => (float) $thisWeekRevenue,
                'this_month' => (float) $thisMonthRevenue,
                'last_month' => (float) $lastMonthRevenue,
                'growth_percentage' => round($growthPercentage, 2),
            ],
        ]);
    }

    /**
     * Get recent activities (orders and manual stock adjustments) with pagination
     */
    public function activities(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $perPage = min($request->input('per_page', 5), 50);

        // Get activities for orders and manual stock adjustments
        $activities = Activity::query()
            ->where(function ($query) use ($tenantId) {
                // Get order activities
                $query->where(function ($q) use ($tenantId) {
                    $q->where('subject_type', Order::class)
                        ->whereHasMorph('subject', [Order::class], function ($subQuery) use ($tenantId) {
                            $subQuery->where('tenant_id', $tenantId);
                        });
                })
                    // Get manual stock adjustment activities
                    ->orWhere(function ($q) use ($tenantId) {
                    $q->where('log_name', 'manual_stock_adjustment')
                        ->whereHasMorph('subject', [Product::class], function ($subQuery) use ($tenantId) {
                            $subQuery->where('tenant_id', $tenantId);
                        });
                });
            })
            ->with(['subject', 'causer'])
            ->latest()
            ->paginate($perPage);

        $transformedActivities = $activities->through(function (Activity $activity) {
            return [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'subject_type' => class_basename($activity->subject_type),
                'subject_id' => $activity->subject_id,
                'subject' => $activity->subject ? [
                    'id' => $activity->subject->id,
                    'name' => $this->getSubjectName($activity),
                ] : null,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->toIso8601String(),
            ];
        });

        // Log::info('Dashboard Activities Response', [
        //     'activities' => $transformedActivities->toArray(),
        // ]);

        return response()->json($transformedActivities);
    }

    /**
     * Get a readable name for the subject
     */
    protected function getSubjectName(Activity $activity): string
    {
        return match (true) {
            $activity->subject instanceof Order => $activity->subject->order_number,
            $activity->subject instanceof Product => $activity->subject->name,
            default => '#' . $activity->subject_id,
        };
    }
}
