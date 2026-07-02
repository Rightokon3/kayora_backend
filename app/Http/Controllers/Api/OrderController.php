<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DeliveryTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // GET /api/orders
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with('items')
            ->latest()
            ->get()
            ->map(fn($order) => $this->transformOrder($order));

        return response()->json($orders);
    }

    // POST /api/orders (Place Order)
    public function store(Request $request)
    {
        $request->validate([
            'delivery_address' => 'required|string',
            'address_label' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'delivery_timing' => 'required|in:asap,scheduled',
            'delivery_date_time' => 'required|date',
            'payment_method' => 'required|in:cash,card',
            'products' => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $subtotalKobo = 0;
            foreach ($request->products as $prod) {
                $subtotalKobo += ($prod['price'] * 100) * $prod['quantity'];
            }

            $deliveryFeeKobo = $this->calculateDeliveryFee($request->delivery_address);
            $totalKobo = $subtotalKobo + $deliveryFeeKobo;

            $order = Order::create([
                'user_id' => Auth::id(),
                'delivery_timing' => $request->delivery_timing,
                'delivery_date_time' => $request->delivery_date_time,
                'status' => 'Pending',
                'payment_method' => $request->payment_method,
                'subtotal_kobo' => $subtotalKobo,
                'delivery_fee_kobo' => $deliveryFeeKobo,
                'total_kobo' => $totalKobo,
                'address_label' => $request->address_label,
                'delivery_address' => $request->delivery_address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            foreach ($request->products as $prod) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $prod['productId'],
                    'name' => $prod['name'],
                    'size' => $prod['size'],
                    'price_kobo' => $prod['price'] * 100,
                    'quantity' => $prod['quantity'],
                ]);
            }

            return response()->json(['success' => true, 'order_id' => $order->id], 201);
        });
    }

    // GET /api/orders/{id}
    public function show($id)
    {
        $order = Order::where('user_id', Auth::id())->with(['items', 'rider'])->findOrFail($id);
        return response()->json($this->transformOrder($order));
    }

    // GET /api/orders/{id}/track
    public function track($id)
    {
        $order = Order::where('user_id', Auth::id())->with('rider')->findOrFail($id);
        return response()->json(['order' => $this->transformOrder($order)]);
    }

    private function calculateDeliveryFee(string $addressText): int
    {
        $cleanAddress = strtolower($addressText);
        $tier = DeliveryTier::all()->first(function ($tier) use ($cleanAddress) {
            return $tier->location_name !== 'default' && str_contains($cleanAddress, $tier->location_name);
        });
        return $tier ? $tier->fee_kobo : DeliveryTier::where('location_name', 'default')->first()->fee_kobo;
    }

    private function transformOrder(Order $order)
    {
        return [
            'id' => $order->id,
            'deliveryTiming' => $order->delivery_timing,
            'deliveryDateTime' => $order->delivery_date_time,
            'status' => $order->status,
            'paymentMethod' => $order->payment_method,
            'subtotal' => $order->subtotal_kobo / 100,
            'deliveryFee' => $order->delivery_fee_kobo / 100,
            'discount' => $order->discount_kobo / 100,
            'total' => $order->total_kobo / 100,
            'createdAt' => $order->created_at->toISOString(),
            'deliveryCompletedAt' => $order->delivery_completed_at?->toISOString(),
            'deliveryAddress' => [
                'label' => $order->address_label,
                'address' => $order->delivery_address,
                'latitude' => (float)$order->latitude,
                'longitude' => (float)$order->longitude,
            ],
            'products' => $order->items->map(fn($item) => [
                'productId' => $item->product_id,
                'name' => $item->name,
                'size' => $item->size,
                'price' => $item->price_kobo / 100,
                'quantity' => $item->quantity,
            ]),
            'timeline' => [
                ['key' => 'placed', 'label' => 'Order Placed', 'completedAt' => $order->created_at->toISOString()],
                ['key' => 'preparing', 'label' => 'Processing Pack', 'completedAt' => in_array($order->status, ['Preparing', 'Active', 'Out for Delivery', 'Completed']) ? $order->created_at->addMinutes(5)->toISOString() : null],
                ['key' => 'dispatch', 'label' => 'Out for Delivery', 'completedAt' => in_array($order->status, ['Out for Delivery', 'Completed']) ? $order->updated_at->toISOString() : null],
                ['key' => 'delivered', 'label' => 'Delivered', 'completedAt' => $order->delivery_completed_at?->toISOString()],
            ],
            'rider' => $order->rider ? [
                'id' => $order->rider->id,
                'fullName' => $order->rider->full_name,
                'phone' => $order->rider->phone,
                'vehicleType' => $order->rider->vehicle_type,
                'motorcycleRegNumber' => $order->rider->motorcycle_reg_number,
                'currentLatitude' => (float)$order->rider->current_latitude,
                'currentLongitude' => (float)$order->rider->current_longitude,
            ] : null
        ];
    }
}