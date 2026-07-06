<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;

class DriverDashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        $driverId = $request->user()->id;
        $today = Carbon::today();

        // 1. Fetch Today's Tasks Scheduled from Admin section
        $tasks = Task::where('driver_id', $driverId)
                     ->whereDate('scheduled_date', $today)
                     ->get();

        // 2. Compute accurate stats aggregations
        $totalDeliveries = Task::where('driver_id', $driverId)->count();
        $completedCount = Task::where('driver_id', $driverId)->where('status', 'completed')->count();
        $pendingCount = Task::where('driver_id', $driverId)->where('status', 'pending')->count();
        $totalDistance = Task::where('driver_id', $driverId)->sum('distance_completed_km');

        return response()->json([
            'success' => true,
            'stats' => [
                'total_deliveries' => $totalDeliveries,
                'completed' => $completedCount,
                'pending' => $pendingCount,
                'distance_km' => round($totalDistance, 2)
            ],
            'tasks' => $tasks
        ]);
    }

    public function updateDriverLocation(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'distance_delta_km' => 'required|numeric'
        ]);

        $task = Task::findOrFail($request->task_id);
        
        // Accumulate distance and update current tracking point
        $task->current_latitude = $request->latitude;
        $task->current_longitude = $request->longitude;
        $task->distance_completed_km += $request->distance_delta_km;
        $task->save();

        return response()->json([
            'success' => true,
            'distance_completed_km' => $task->distance_completed_km
        ]);
    }
}
