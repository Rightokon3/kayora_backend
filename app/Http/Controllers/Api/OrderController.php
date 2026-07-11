<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

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

    private function transformOrder(Order $order)
    {
        return [
            'id' => (string) $order->id,
            'deliveryTiming' => $order->delivery_type,
            'deliveryDateTime' => trim($order->scheduled_date . ' ' . $order->scheduled_time),
            'status' => $order->status,
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
                ['key' => 'preparing', 'label' => 'Processing Pack', 'completedAt' => in_array($order->status, ['Preparing', 'Active', 'Out for Delivery', 'Completed']) ? $order->started_at?->toISOString() : null],
                ['key' => 'dispatch', 'label' => 'Out for Delivery', 'completedAt' => in_array($order->status, ['Out for Delivery', 'Completed']) ? $order->updated_at->toISOString() : null],
                ['key' => 'delivered', 'label' => 'Delivered', 'completedAt' => $order->completed_at?->toISOString()],
            ],
            'rider' => $order->driver ? [
                'id' => $order->driver->id,
                'fullName' => $order->driver->full_name ?? $order->driver->name ?? '',
                'phone' => $order->driver->phone ?? '',
                'vehicleType' => $order->driver->vehicle_type ?? '',
                'motorcycleRegNumber' => $order->driver->motorcycle_reg_number ?? '',
                'currentLatitude' => (float) ($order->driver->current_latitude ?? 0),
                'currentLongitude' => (float) ($order->driver->current_longitude ?? 0),
            ] : null,
        ];
    }
}