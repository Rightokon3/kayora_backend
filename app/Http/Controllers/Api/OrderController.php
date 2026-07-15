<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderController extends Controller
{
    // GET /api/orders
    public function index(Request $request)
    {
        $orders = Order::where('customer_email', $request->user()->email)
            ->with('items')
            ->latest()
            ->get()
            ->map(fn($order) => $this->transformOrder($order));

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    // GET /api/orders/{id}
    public function show(Request $request, $id)
    {
        $order = Order::where('customer_email', $request->user()->email)
            ->with(['items', 'driver'])
            ->findOrFail($id);

        return response()->json($this->transformOrder($order));
    }

    // GET /api/orders/{id}/track
    public function track(Request $request, $id)
    {
        $order = Order::where('customer_email', $request->user()->email)
            ->with('driver')
            ->findOrFail($id);

        return response()->json(['order' => $this->transformOrder($order)]);
    }

    /**
     * Maps the real database status enum onto the simplified set of
     * statuses the customer app's UI actually understands (used for the
     * Active/Past tab filter and the status badge colors). The database
     * enum ('Pending','Accepted','Assigned','Scheduled','Preparing',
     * 'Out For Delivery','Delivered','Cancelled') doesn't line up 1:1
     * with the app's ('Pending','Preparing','Active','Out for Delivery',
     * 'Completed','Cancelled') — this is why accepted orders were
     * silently vanishing from the Active tab before.
     */
    private function mapStatus(string $dbStatus): string
    {
        return match ($dbStatus) {
            'Accepted', 'Assigned' => 'Active',
            'Out For Delivery' => 'Out for Delivery',
            'Delivered' => 'Completed',
            'Scheduled' => 'Pending',
            default => $dbStatus, // Pending, Preparing, Cancelled already match
        };
    }

    private function transformOrder(Order $order)
    {
        $status = $this->mapStatus($order->status);

        // scheduled_date is cast to a Carbon `date` on the Order model (not a
        // plain string), so ->format('Y-m-d') is needed here — concatenating
        // the Carbon object directly with '.' calls its __toString(), which
        // returns a full datetime and produces a malformed string that
        // Carbon::parse() can't read, throwing an exception (our 500).
        $deliveryDateTime = ($order->scheduled_date && $order->scheduled_time)
            ? Carbon::parse($order->scheduled_date->format('Y-m-d') . ' ' . $order->scheduled_time)
            : $order->created_at;

        return [
            'id' => (string) $order->id,
            'deliveryTiming' => $order->delivery_type,
            'deliveryDateTime' => $deliveryDateTime->toISOString(),
            'status' => $status,
            'paymentMethod' => $order->payment_method,
            'subtotal' => (float) $order->amount,
            'deliveryFee' => 0,
            'discount' => 0,
            'total' => (float) $order->amount,
            'createdAt' => $order->created_at->toISOString(),
            'deliveryCompletedAt' => $order->completed_at?->toISOString(),
            'deliveryAddress' => [
                'label' => $order->nearest_landmark,
                'address' => $order->delivery_address,
                'latitude' => (float) $order->latitude,
                'longitude' => (float) $order->longitude,
            ],
            'products' => $order->items->map(fn($item) => [
                'productId' => null,
                'name' => $item->bottle_name,
                'size' => $item->size,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
            ]),
            'timeline' => [
                ['key' => 'placed', 'label' => 'Order Placed', 'completedAt' => $order->created_at->toISOString()],
                ['key' => 'preparing', 'label' => 'Processing Pack', 'completedAt' => in_array($status, ['Preparing', 'Active', 'Out for Delivery', 'Completed']) ? $order->started_at?->toISOString() : null],
                ['key' => 'dispatch', 'label' => 'Out for Delivery', 'completedAt' => in_array($status, ['Out for Delivery', 'Completed']) ? $order->updated_at->toISOString() : null],
                ['key' => 'delivered', 'label' => 'Delivered', 'completedAt' => $order->completed_at?->toISOString()],
            ],
            'rider' => $order->driver ? [
                'id' => $order->driver->id,
                'fullName' => $order->driver->name ?? '',
                'phone' => $order->driver->phone ?? '',
                'vehicleType' => $order->driver->vehicle ?? '',
                'motorcycleRegNumber' => $order->driver->plate_number ?? '',
                'currentLatitude' => (float) ($order->driver->current_latitude ?? 0),
                'currentLongitude' => (float) ($order->driver->current_longitude ?? 0),
            ] : null,
        ];
    }
}