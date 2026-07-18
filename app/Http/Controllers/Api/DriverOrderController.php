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
        //
        // NOTE: intentionally returning raw Eloquent JSON here (snake_case),
        // NOT a hand-built camelCase array — DriverOrdersService.ts's own
        // mapOrder()/mapItem() already do that conversion on the frontend.
        // Converting here too was the bug: it fed already-camelCase JSON
        // into code that was looking for raw.customer_name, raw.order_number,
        // etc., so anything with an actual case difference came back
        // undefined while single-word fields (status, priority, quantity)
        // happened to look fine either way.
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
        $driverId = $request->user()->id;

        // Two valid paths to accept an order:
        // (1) Legacy broadcast flow — order was offered to this driver via
        //     offered_driver_id, awaiting a response.
        // (2) New flow — the customer picked this driver directly at
        //     checkout, so driver_id is already set and status is
        //     'Assigned', with no offer step. Either way, the driver
        //     hasn't confirmed yet, so both are acceptable here.
        $isOfferedToMe = $order->offered_driver_id === $driverId;
        $isDirectlyAssignedToMe = $order->driver_id === $driverId && $order->status === 'Assigned';

        abort_unless($isOfferedToMe || $isDirectlyAssignedToMe, 403, 'This order was not offered to you.');

        $order->update([
            'driver_id' => $driverId,
            'offered_driver_id' => null,
            'status' => 'Accepted',
            'assigned_at' => $order->assigned_at ?? now(),
        ]);

        return response()->json($order->load('items'));
    }

    public function decline(Request $request, Order $order)
    {
        $driverId = $request->user()->id;

        $isOfferedToMe = $order->offered_driver_id === $driverId;
        $isDirectlyAssignedToMe = $order->driver_id === $driverId && $order->status === 'Assigned';

        abort_unless($isOfferedToMe || $isDirectlyAssignedToMe, 403, 'This order was not offered to you.');

        $order->declines()->create(['driver_id' => $driverId]);

        if ($isDirectlyAssignedToMe) {
            // ⚠️ Confirm this is the behavior you want: the customer
            // specifically picked this driver, so there's no "offer the
            // next nearest driver" fallback to reach for. This clears the
            // assignment and puts the order back to Pending.
            $order->update([
                'driver_id' => null,
                'status' => 'Pending',
                'assigned_at' => null,
            ]);
        } else {
            $order->update(['offered_driver_id' => null]);

            // Immediately try the next nearest driver — this is what makes
            // ASAP dispatch actually keep moving instead of stalling on
            // one decline.
            app(OrderDispatchService::class)->offerToNearestDriver($order->fresh());
        }

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