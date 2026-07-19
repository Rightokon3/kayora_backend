<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RevenueEntry;
use App\Models\User;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard/stats
     * Percent-change badges compare "new records created this calendar
     * month" vs "new records created last calendar month" — activeDrivers
     * is the one exception: duty_status is a live snapshot with no
     * history table behind it, so there's nothing to compare it against.
     * Its changePct is always 0 until a driver-status history table
     * exists; flagging this rather than inventing a number.
     */
    public function stats()
    {
        $now = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = $startOfThisMonth->copy()->subSecond();

        $pctChange = function (int $current, int $previous): float {
            if ($previous === 0) {
                return $current > 0 ? 100.0 : 0.0;
            }
            return round((($current - $previous) / $previous) * 100, 1);
        };

        $customersThisMonth = User::where('created_at', '>=', $startOfThisMonth)->count();
        $customersLastMonth = User::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $ordersThisMonth = Order::where('created_at', '>=', $startOfThisMonth)->count();
        $ordersLastMonth = Order::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $revenueThisMonth = (float) RevenueEntry::where('entry_date', '>=', $startOfThisMonth)->sum('amount');
        $revenueLastMonth = (float) RevenueEntry::whereBetween('entry_date', [$startOfLastMonth, $endOfLastMonth])->sum('amount');

        return response()->json([
            'totalCustomers' => User::count(),
            'totalCustomersChangePct' => $pctChange($customersThisMonth, $customersLastMonth),

            'activeDrivers' => Driver::where('duty_status', 'on_duty')->count(),
            'activeDriversChangePct' => 0, // see method doc — no history table to compare against yet

            'totalOrders' => Order::count(),
            'totalOrdersChangePct' => $pctChange($ordersThisMonth, $ordersLastMonth),

            'revenue' => (float) RevenueEntry::sum('amount'),
            'revenueChangePct' => $pctChange((int) round($revenueThisMonth), (int) round($revenueLastMonth)),
        ]);
    }

    /**
     * GET /api/admin/dashboard/revenue-monthly
     * Sum of revenue_entries.amount per calendar month, Jan–Dec of the
     * current year. Months with no entries yet return 0, not omitted,
     * so the chart's x-axis always has all 12 points.
     */
    public function monthlyRevenue()
    {
        $year = Carbon::now()->year;

        $rows = RevenueEntry::selectRaw('MONTH(entry_date) as month, SUM(amount) as total')
            ->whereYear('entry_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $result = [];
        foreach ($monthLabels as $i => $label) {
            $monthNumber = $i + 1;
            $result[] = [
                'month' => $label,
                'revenue' => (float) ($rows[$monthNumber] ?? 0),
            ];
        }

        return response()->json($result);
    }

    /**
     * GET /api/admin/dashboard/orders-weekly
     * Order count per day for the current week (Monday–Sunday), based on
     * orders.created_at — "orders made in a day" per the request.
     */
    public function weeklyOrders()
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $rows = Order::selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('day')
            ->pluck('total', 'day');

        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        $result = [];
        $cursor = $startOfWeek->copy();
        foreach ($dayLabels as $label) {
            $key = $cursor->toDateString();
            $result[] = [
                'day' => $label,
                'orders' => (int) ($rows[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return response()->json($result);
    }

    /**
     * GET /api/admin/dashboard/order-categories
     * Distribution of order_items by `size` — percentage of total items
     * ordered per size, computed from quantity (not line-item count), so
     * a single line with quantity=10 counts as 10 units of that size, not 1.
     */
    public function orderCategories()
    {
        $rows = \DB::table('order_items')
            ->selectRaw('size, SUM(quantity) as total_quantity')
            ->groupBy('size')
            ->orderByDesc('total_quantity')
            ->get();

        $grandTotal = $rows->sum('total_quantity');

        return response()->json(
            $rows->map(function ($row) use ($grandTotal) {
                return [
                    'size' => $row->size,
                    'quantity' => (int) $row->total_quantity,
                    'percentage' => $grandTotal > 0 ? round(($row->total_quantity / $grandTotal) * 100, 1) : 0,
                ];
            })->values()
        );
    }

    /**
     * GET /api/admin/dashboard/recent-orders
     * Latest N orders (not all orders — this is the dashboard preview
     * table, not the full Orders page), newest first. Field names here
     * match RecentOrdersTable.tsx's prop usage exactly (id, customerName,
     * bottleName, driverName) — not the raw orders table column names.
     */
    public function recentOrders(Request $request)
    {
        $limit = (int) $request->query('limit', 6);

        $orders = Order::with(['items', 'driver'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json(
            $orders->map(function ($order) {
                $firstItem = $order->items->first();
                $totalQuantity = $order->items->sum('quantity');
                $bottleName = $order->items->count() > 1
                    ? ($firstItem?->bottle_name ?? 'Order') . ' +' . ($order->items->count() - 1) . ' more'
                    : ($firstItem?->bottle_name ?? '—');

                return [
                    'id' => $order->order_number,
                    'customerName' => $order->customer_name,
                    'bottleName' => $bottleName,
                    'quantity' => $totalQuantity,
                    'amount' => (float) $order->amount,
                    'status' => $order->status,
                    'driverName' => $order->driver?->name,
                    'createdAt' => $order->created_at?->toIso8601String() ?? '',
                ];
            })
        );
    }

    /**
     * POST /api/admin/dashboard/revenue
     * Lets an admin log a day's revenue — this is the "paste their
     * revenue" entry point the monthly chart and stats card both read
     * from.
     */
    public function storeRevenue(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $entry = RevenueEntry::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'entry' => $entry], 201);
    }
}