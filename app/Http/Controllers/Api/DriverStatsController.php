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

        // Each count is gated by the date column that's actually relevant to
        // it, not by the order's created_at. created_at is when the customer
        // originally placed the order — it can be any earlier date even when
        // the admin assigns it to this driver today, or when the driver
        // completes it today. Gating everything on created_at meant a
        // delivery finished just now could still show up as 0 completed if
        // the underlying order wasn't itself created today.
        $completed = Order::where('driver_id', $driverId)
            ->where('status', 'Delivered')
            ->whereDate('completed_at', $today)
            ->count();

        // Active/pending reflect the driver's CURRENT queue — these statuses
        // are inherently "now", so they aren't date-gated at all.
        $active = Order::where('driver_id', $driverId)
            ->where('status', 'Out For Delivery')
            ->count();

        $pending = Order::where('driver_id', $driverId)
            ->whereIn('status', ['Assigned', 'Preparing'])
            ->count();

        $total = $completed + $active + $pending;

        $stat = \App\Models\DriverDailyStat::where('driver_id', $driverId)->where('date', $today)->first();

        return response()->json([
            'todayDeliveries' => $total,
            'completed' => $completed,
            'pending' => $pending,
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