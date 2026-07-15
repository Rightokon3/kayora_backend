<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use Carbon\Carbon;

class CartOrderController extends Controller
{
    // 1. Fetch current database cart state
    public function getCart(Request $request) {
        $user = $request->user();
        // Fallback mock array structure if tables aren't completely migrated
        $cart = $user->cart_meta ?? []; 
        return response()->json(['success' => true, 'cart' => $cart]);
    }

    // 2. Add an item to the server cart map (increments quantity if already present)
    public function addToCart(Request $request) {
        $request->validate([
            'product_id' => 'required',
            'quantity'   => 'integer|min:1',
        ]);
        $user = $request->user();
        $quantityToAdd = $request->quantity ?? 1;

        $cart = $user->cart_meta ?? [];
        $found = false;
        foreach ($cart as &$item) {
            if ($item['productId'] == $request->product_id) {
                $item['quantity'] += $quantityToAdd;
                $found = true;
                break;
            }
        }
        unset($item); // break the reference from the foreach loop

        if (!$found) {
            $cart[] = ['productId' => $request->product_id, 'quantity' => $quantityToAdd];
        }

        $user->cart_meta = $cart;
        $user->save();
        return response()->json(['success' => true, 'cart' => $cart]);
    }

    // 3. Synchronize updating quantities
    public function updateQuantity(Request $request) {
        $request->validate(['product_id' => 'required', 'quantity' => 'required|integer']);
        $user = $request->user();
        
        $cart = $user->cart_meta ?? [];
        $found = false;
        foreach($cart as &$item) {
            if($item['productId'] == $request->product_id) {
                $item['quantity'] = $request->quantity;
                $found = true;
            }
        }
        if(!$found) {
            $cart[] = ['productId' => $request->product_id, 'quantity' => $request->quantity];
        }
        
        $user->cart_meta = $cart;
        $user->save();
        return response()->json(['success' => true, 'cart' => $cart]);
    }

    // 4. Remove an item from the server cart map
    public function removeFromCart(Request $request, $productId) {
        $user = $request->user();
        $cart = collect($user->cart_meta ?? [])->filter(function($item) use ($productId) {
            return $item['productId'] != $productId;
        })->values()->toArray();
        
        $user->cart_meta = $cart;
        $user->save();
        return response()->json(['success' => true, 'cart' => $cart]);
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'addressId'              => 'required',
            'paymentMethod'          => 'required|in:cash,card',
            'deliveryTiming'         => 'required|in:asap,scheduled',
            'deliveryDateTime'       => 'required',
            // NEW: required only for ASAP orders — the driver the customer picked
            // themselves, instead of the order being broadcast to every online driver.
            'driverId'               => 'nullable|required_if:deliveryTiming,asap|integer|exists:drivers,id',
            'cartItems'              => 'required|array',
            'cartItems.*.productId'  => 'required|integer',
            'cartItems.*.quantity'   => 'required|integer|min:1',
            'cartItems.*.price'      => 'required|numeric',
            'subtotal'               => 'required|integer',
            'deliveryFee'            => 'required|integer',
            'serviceFee'             => 'required|integer',
            'total'                  => 'required|integer',
        ]);

        try {
            $user = $request->user();

            // ─── CHECK CARDS IF ATTEMPTING CARD METHOD ───
            if ($validated['paymentMethod'] === 'card' && empty($user->payment_methods)) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'NO_PAYMENT_METHOD',
                    'message' => 'No card added to account'
                ], 422);
            }

            // Look up the saved address the customer picked, so we can flatten it
            // into the delivery_address/latitude/longitude columns Order expects.
            $address = Address::where('id', $validated['addressId'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $deliveryDateTime = Carbon::parse($validated['deliveryDateTime']);
            $isAsap = $validated['deliveryTiming'] === 'asap';

            $order = Order::create([
                'order_number'      => 'ORD-' . strtoupper(uniqid()),
                // Assign the customer-picked driver straight to the order for
                // ASAP deliveries — skipping the offered_driver_id "offer and
                // wait for acceptance" step entirely, since the customer has
                // already made the choice. Scheduled orders stay unassigned.
                'driver_id'         => $isAsap ? $validated['driverId'] : null,
                'customer_name'     => $user->name,
                'customer_phone'    => $user->phone ?? '',
                'customer_email'    => $user->email,
                'delivery_address'  => $address->address,
                'nearest_landmark'  => $address->label,
                'latitude'          => $address->latitude,
                'longitude'         => $address->longitude,
                'amount'            => $validated['total'],
                'status'            => $isAsap ? 'Assigned' : 'Pending',
                'payment_method'    => $validated['paymentMethod'],
                'payment_status'    => $validated['paymentMethod'] === 'cash' ? 'pending' : 'paid',
                'delivery_type'     => $isAsap ? 'Instant' : 'Scheduled',
                'scheduled_date'    => $deliveryDateTime->toDateString(),
                'scheduled_time'    => $deliveryDateTime->toTimeString(),
                'priority'          => 'Normal',
                'assigned_at'       => $isAsap ? now() : null,
            ]);

            // No further driver-status mutation needed here — the
            // whereNotIn(...) subquery in DriverDiscoveryController::nearby()
            // already excludes this driver from future picks by checking for
            // this exact active order, so their duty_status (their own shift
            // toggle) is left untouched.

            // Persist each cart line as its own OrderItem row via the items() relation.
            // OrderItem stores a denormalized snapshot (bottle_name, size, subtotal),
            // not a product_id, so we look each product up to fill those in.
            foreach ($validated['cartItems'] as $line) {
                $product = Product::find($line['productId']);

                $order->items()->create([
                    'bottle_name' => $product->name ?? 'Unknown Product',
                    'size'        => $product->size ?? '',
                    'quantity'    => $line['quantity'],
                    'price'       => $line['price'],
                    'subtotal'    => $line['quantity'] * $line['price'],
                ]);
            }

            // Clear the cart now that the order is saved
            $user->cart_meta = [];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'order'   => $order->load('items'),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Order Placement Crash: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal failure to write order to disk.'
            ], 500);
        }
    }
}