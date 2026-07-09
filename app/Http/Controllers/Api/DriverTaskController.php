<?php

// app/Http/Controllers/Api/DriverTaskController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DriverTaskController extends Controller
{
    public function today(Request $request)
    {
        $orders = Order::with('items')
            ->where('driver_id', $request->user()->id)
            ->whereIn('status', ['Assigned', 'Out For Delivery', 'Preparing'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('priority')
            ->get();

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->driver_id === $request->user()->id, 403);

        return response()->json($order->load('items', 'driver'));
    }

    public function start(Request $request, Order $order)
    {
        abort_unless($order->driver_id === $request->user()->id, 403);

        $order->update(['status' => 'Out For Delivery', 'started_at' => now()]);

        return response()->json($order->load('items'));
    }

    public function complete(Request $request, Order $order)
    {
        abort_unless($order->driver_id === $request->user()->id, 403);

        $order->update(['status' => 'Delivered', 'completed_at' => now()]);

        return response()->json($order->load('items'));
    }
}