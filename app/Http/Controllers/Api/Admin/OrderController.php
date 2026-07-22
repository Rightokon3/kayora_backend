<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Services\HaversineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    /**
     * GET /api/admin/orders?search=&status=&deliveryType=&date=
     * Real backend search/filter — not client-side filtering. `status`
     * matches the orders.status enum exactly. `deliveryType` is 'asap' or
     * 'scheduled' (case-insensitive, since the DB has mixed-case legacy
     * values). `date` (YYYY-MM-DD) filters by scheduled_date — this is
     * what powers the "what's scheduled today" calendar filter.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $deliveryType = $request->query('deliveryType');
        $date = $request->query('date');

        $query = Order::with(['items', 'driver.vehicleAssignment', 'driver.profile']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('delivery_address', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('bottle_name', 'like', "%{$search}%")
                            ->orWhere('size', 'like', "%{$search}%");
                    });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($deliveryType) {
            $query->whereRaw('LOWER(delivery_type) = ?', [strtolower((string) $deliveryType)]);
        }

        if ($date) {
            $query->whereDate('scheduled_date', $date);
        }

        $orders = $query->orderByDesc('created_at')->get();

        return response()->json($orders->map(fn ($o) => $this->transform($o)));
    }

    /**
     * PUT /api/admin/orders/{orderNumber}
     * Looked up by order_number (what the frontend calls order.id), not
     * the numeric primary key — matches what EditOrderModal/DeleteOrderModal
     * already send.
     */
    public function update(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $validated = $request->validate([
            'deliveryDate' => 'nullable|string',
            'deliveryTime' => 'nullable|string',
            'deliveryAddress' => 'nullable|string|max:255',
            'deliveryNotes' => 'nullable|string',
            'paymentStatus' => 'nullable|in:Paid,Unpaid,Refunded',
            'status' => 'nullable|in:Pending,Accepted,Assigned,Scheduled,Preparing,Out For Delivery,Delivered,Cancelled',
            'priority' => 'nullable|in:Normal,High,Urgent',
            'driverId' => 'nullable',
            'specialInstructions' => 'nullable|string',
        ]);

        $update = [];

        if (!empty($validated['deliveryAddress'])) {
            $update['delivery_address'] = $validated['deliveryAddress'];
        }
        if (array_key_exists('deliveryNotes', $validated) && $validated['deliveryNotes'] !== null) {
            $update['special_instructions'] = $validated['deliveryNotes'];
        }
        if (!empty($validated['deliveryDate'])) {
            try {
                $update['scheduled_date'] = Carbon::parse($validated['deliveryDate'])->toDateString();
            } catch (\Exception $e) {
                // Unparseable date string (e.g. leftover placeholder text) —
                // leave scheduled_date untouched rather than crash the save.
            }
        }
        if (array_key_exists('deliveryTime', $validated)) {
            $update['scheduled_time'] = $validated['deliveryTime'];
        }
        if (!empty($validated['paymentStatus'])) {
            $update['payment_status'] = $validated['paymentStatus'];
        }
        if (!empty($validated['status'])) {
            $update['status'] = $validated['status'];
        }
        if (!empty($validated['priority'])) {
            $update['priority'] = $validated['priority'];
        }
        if (array_key_exists('driverId', $validated)) {
            $update['driver_id'] = $validated['driverId'] ?: null;
            if ($validated['driverId'] && !$order->assigned_at) {
                $update['assigned_at'] = now();
            }
        }

        $order->update($update);

        return response()->json($this->transform($order->fresh(['items', 'driver.vehicleAssignment', 'driver.profile'])));
    }

    /**
     * DELETE /api/admin/orders/{orderNumber}
     */
    public function destroy(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->items()->delete();
        $order->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/admin/orders/{orderNumber}/available-drivers
     * "Any driver listed and active" — every on_duty driver, not
     * restricted by distance or anything else. distanceKm is shown for
     * context only, it doesn't filter the list.
     */
    public function availableDrivers(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $drivers = Driver::where('duty_status', 'on_duty')
            ->with(['vehicleAssignment', 'profile'])
            ->get();

        return response()->json($drivers->map(function ($driver) use ($order) {
            $activeStatuses = ['Accepted', 'Assigned', 'Preparing', 'Out For Delivery'];

            $activeCount = Order::where('driver_id', $driver->id)
                ->whereIn('status', $activeStatuses)
                ->count();

            $distanceKm = ($driver->current_latitude !== null && $driver->current_longitude !== null)
                ? HaversineService::distanceKm(
                    (float) $driver->current_latitude,
                    (float) $driver->current_longitude,
                    (float) $order->latitude,
                    (float) $order->longitude
                  )
                : null;

            return [
                'id' => (string) $driver->id,
                'driverId' => $driver->driver_id,
                'name' => $driver->name,
                'profileImage' => $driver->profile?->profile_image,
                'status' => $activeCount > 0 ? 'delivering' : 'active',
                'phone' => $driver->phone ?? '',
                'vehicle' => $driver->vehicleAssignment
                    ? "{$driver->vehicleAssignment->brand} {$driver->vehicleAssignment->model}"
                    : 'No vehicle assigned',
                'distanceKm' => $distanceKm !== null ? round($distanceKm, 1) : 0,
                'assignedDeliveries' => $activeCount,
            ];
        }));
    }

    /**
     * POST /api/admin/orders/{orderNumber}/assign
     */
    public function assignDriver(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $validated = $request->validate(['driverId' => 'required']);

        $order->update([
            'driver_id' => $validated['driverId'],
            'status' => 'Assigned',
            'assigned_at' => now(),
        ]);

        return response()->json($this->transform($order->fresh(['items', 'driver.vehicleAssignment', 'driver.profile'])));
    }

    private function transform(Order $order): array
    {
        $rawDeliveryType = strtolower((string) $order->delivery_type);

        return [
            'id' => $order->order_number,
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email ?? '—',
                'deliveryAddress' => $order->delivery_address,
                'nearestLandmark' => $order->nearest_landmark ?? '—',
                'latitude' => (float) $order->latitude,
                'longitude' => (float) $order->longitude,
                // orders has no FK to the users table (customer_name/phone/
                // email are stored denormalized), so there's no reliable
                // way to look up a profile picture here — omitted rather
                // than guessed via a name/phone match.
                'profilePicture' => null,
            ],
            'products' => $order->items->map(fn ($i) => [
                'bottleName' => $i->bottle_name,
                'size' => $i->size,
                'quantity' => $i->quantity,
                'price' => (float) $i->price,
                'subtotal' => (float) $i->subtotal,
            ])->values(),
            'amount' => (float) $order->amount,
            'status' => $order->status,
            'paymentMethod' => $order->payment_method ?? '—',
            'paymentStatus' => $this->normalizePaymentStatus($order->payment_status),
            'transactionId' => $order->transaction_id ?? '—',
            'deliveryType' => in_array($rawDeliveryType, ['asap', 'instant'], true) ? 'Instant' : 'Scheduled',
            'scheduledDate' => optional($order->scheduled_date)->toDateString(),
            'scheduledTime' => $order->scheduled_time,
            'priority' => ucfirst(strtolower($order->priority)),
            'specialInstructions' => $order->special_instructions ?? '',
            'orderDate' => optional($order->created_at)->toIso8601String() ?? '',
            'delivery' => [
                'driverId' => $order->driver_id ? (string) $order->driver_id : null,
                'driverName' => $order->driver->name ?? null,
                'vehicle' => $order->driver?->vehicleAssignment
                    ? "{$order->driver->vehicleAssignment->brand} {$order->driver->vehicleAssignment->model}"
                    : null,
                'estimatedDeliveryTime' => $order->eta,
                'distanceKm' => $order->distance_km !== null ? (float) $order->distance_km : null,
            ],
            'timeline' => $this->buildTimeline($order),
        ];
    }

    private function normalizePaymentStatus(?string $raw): string
    {
        $lower = strtolower((string) $raw);
        if ($lower === 'paid') return 'Paid';
        if ($lower === 'refunded') return 'Refunded';
        return 'Unpaid'; // covers 'pending', null, and anything unrecognized
    }

    private function buildTimeline(Order $order): array
    {
        if ($order->status === 'Cancelled') {
            return [
                ['label' => 'Order Placed', 'completed' => true, 'timestamp' => optional($order->created_at)->toIso8601String()],
                ['label' => 'Cancelled', 'completed' => true, 'timestamp' => optional($order->updated_at)->toIso8601String()],
            ];
        }

        return [
            ['label' => 'Order Placed', 'completed' => true, 'timestamp' => optional($order->created_at)->toIso8601String()],
            ['label' => 'Driver Assigned', 'completed' => $order->assigned_at !== null, 'timestamp' => optional($order->assigned_at)->toIso8601String()],
            ['label' => 'Out For Delivery', 'completed' => $order->started_at !== null, 'timestamp' => optional($order->started_at)->toIso8601String()],
            ['label' => 'Delivered', 'completed' => $order->completed_at !== null, 'timestamp' => optional($order->completed_at)->toIso8601String()],
        ];
    }
}