<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverDailyStat;
use App\Models\Order;
use Illuminate\Http\Request;

class DriverStatsController extends Controller
{
    public function today(Request $request)
    {
        $driverId = $request->user()->id;
        $today = now()->toDateString();

        $todaysOrders = Order::where('driver_id', $driverId)
            ->whereDate('created_at', $today);

        $completed = (clone $todaysOrders)->where('status', 'Delivered')->count();
        $total = (clone $todaysOrders)->count();
        $pending = $total - $completed;

        $stat = DriverDailyStat::where('driver_id', $driverId)->where('date', $today)->first();

        return response()->json([
            'todayDeliveries' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'distanceKm' => $stat ? round($stat->distance_km, 2) : 0,
        ]);
    }
}