<?php

// app/Http/Controllers/Api/DriverOrderController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\HaversineService;
use App\Services\OrderDispatchService;
use Illuminate\Http\Request;

class DriverOrderController extends Controller
{
    public function index(Request $request)
    {
        $driverId = $request->user()->id;

        // Everything actually assigned to me, PLUS any ASAP order currently
        // offered to me and awaiting my accept/decline.
        $orders = Order::with('items')
            ->where(function ($query) use ($driverId) {
                $query->where('driver_id', $driverId)
                    ->orWhere('offered_driver_id', $driverId);
            })
            ->whereIn('status', ['Pending', 'Accepted', 'Assigned', 'Preparing', 'Out For Delivery', 'Delivered'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeAccess($request, $order);
        return response()->json($order->load('items'));
    }

    public function accept(Request $request, Order $order)
    {
        abort_unless($order->offered_driver_id === $request->user()->id, 403, 'This order was not offered to you.');

        $order->update([
            'driver_id' => $request->user()->id,
            'offered_driver_id' => null,
            'status' => 'Assigned',
            'assigned_at' => now(),
        ]);

        return response()->json($order->load('items'));
    }

    public function decline(Request $request, Order $order)
    {
        abort_unless($order->offered_driver_id === $request->user()->id, 403, 'This order was not offered to you.');

        $order->declines()->create(['driver_id' => $request->user()->id]);
        $order->update(['offered_driver_id' => null]);

        // Immediately try the next nearest driver — this is what makes ASAP
        // dispatch actually keep moving instead of stalling on one decline.
        app(OrderDispatchService::class)->offerToNearestDriver($order->fresh());

        return response()->json(['message' => 'Order declined']);
    }

    public function start(Request $request, Order $order)
    {
        $this->authorizeAccess($request, $order);
        $order->update(['status' => 'Out For Delivery', 'started_at' => now()]);
        return response()->json($order->load('items'));
    }

    public function complete(Request $request, Order $order)
    {
        $this->authorizeAccess($request, $order);
        $order->update(['status' => 'Delivered', 'completed_at' => now()]);
        return response()->json($order->load('items'));
    }

    public function track(Request $request, Order $order)
    {
        $this->authorizeAccess($request, $order);
        $driver = $request->user();

        $distanceKm = ($driver->current_latitude && $driver->current_longitude)
            ? HaversineService::distanceKm(
                (float) $driver->current_latitude,
                (float) $driver->current_longitude,
                (float) $order->latitude,
                (float) $order->longitude
              )
            : null;

        return response()->json([
            'driverLatitude' => $driver->current_latitude,
            'driverLongitude' => $driver->current_longitude,
            'destinationLatitude' => $order->latitude,
            'destinationLongitude' => $order->longitude,
            'distanceKm' => $distanceKm,
        ]);
    }

    private function authorizeAccess(Request $request, Order $order): void
    {
        abort_unless($order->driver_id === $request->user()->id, 403);
    }
}
