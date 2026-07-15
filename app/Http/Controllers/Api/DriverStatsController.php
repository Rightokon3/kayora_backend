<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DriverStatsController extends Controller
{
    public function today(Request $request)
    {
        $driverId = $request->user()->id;
        $today = now()->toDateString();

        $todaysOrders = Order::where('driver_id', $driverId)
            ->whereDate('created_at', $today);

        $completed = (clone $todaysOrders)->where('status', 'Delivered')->count();
        $active = (clone $todaysOrders)->where('status', 'Out For Delivery')->count();
        $total = (clone $todaysOrders)->count();
        $pending = $total - $completed - $active;

        $stat = \App\Models\DriverDailyStat::where('driver_id', $driverId)->where('date', $today)->first();

        return response()->json([
            'todayDeliveries' => $total,
            'completed' => $completed,
            'pending' => max(0, $pending),
            // Added for tasks.tsx's "Active Deliveries" card — dashboard.tsx
            // already ignores extra fields it doesn't use, so both screens
            // share this one endpoint instead of duplicating the query.
            'active' => $active,
            'distanceKm' => $stat ? round($stat->distance_km, 2) : 0,
        ]);
    }

    /**
     * GET /driver/tasks/performance?range=day|week|month|year
     *
     * Powers tasks.tsx's Delivery Performance chart. Counts THIS driver's
     * Delivered orders (by completed_at), bucketed by the requested range.
     * Computed in PHP with simple date-range loops rather than DB-specific
     * GROUP BY/DATE_FORMAT SQL, so it isn't tied to MySQL-only syntax.
     */
    public function performance(Request $request)
    {
        $request->validate([
            'range' => 'required|in:day,week,month,year',
        ]);

        $driverId = $request->user()->id;
        $range = $request->range;

        $baseQuery = fn (Carbon $start, Carbon $end) => Order::where('driver_id', $driverId)
            ->where('status', 'Delivered')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        $data = [];

        if ($range === 'day') {
            // Every 2-hour bucket from 6AM to 8PM today.
            $labels = ['6AM', '8AM', '10AM', '12PM', '2PM', '4PM', '6PM', '8PM'];
            $startOfDay = now()->startOfDay()->setHour(6);
            foreach ($labels as $i => $label) {
                $bucketStart = $startOfDay->copy()->addHours($i * 2);
                $bucketEnd = $bucketStart->copy()->addHours(2)->subSecond();
                $data[] = ['label' => $label, 'value' => $baseQuery($bucketStart, $bucketEnd)];
            }
        } elseif ($range === 'week') {
            $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $startOfWeek = now()->startOfWeek();
            foreach ($labels as $i => $label) {
                $day = $startOfWeek->copy()->addDays($i);
                $data[] = ['label' => $label, 'value' => $baseQuery($day->copy()->startOfDay(), $day->copy()->endOfDay())];
            }
        } elseif ($range === 'month') {
            $startOfMonth = now()->startOfMonth();
            for ($i = 0; $i < 4; $i++) {
                $weekStart = $startOfMonth->copy()->addWeeks($i);
                $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
                $data[] = ['label' => 'Wk ' . ($i + 1), 'value' => $baseQuery($weekStart, $weekEnd)];
            }
        } else { // year
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $startOfYear = now()->startOfYear();
            foreach ($labels as $i => $label) {
                $monthStart = $startOfYear->copy()->addMonths($i);
                $monthEnd = $monthStart->copy()->endOfMonth();
                if ($monthStart->greaterThan(now())) break; // don't show future months
                $data[] = ['label' => $label, 'value' => $baseQuery($monthStart, $monthEnd)];
            }
        }

        return response()->json($data);
    }
}