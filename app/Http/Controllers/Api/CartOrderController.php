<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class CartOrderController extends Controller
{
    // 1. Fetch current database cart state
    public function getCart(Request $request) {
        $user = $request->user();
        // Fallback mock array structure if tables aren't completely migrated
        $cart = $user->cart_meta ?? []; 
        return response()->json(['success' => true, 'cart' => $cart]);
    }

    // 2. Synchronize updating quantities
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

    // 3. Remove an item from the server cart map
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
    // 1. Validate incoming parameters match the exact keys from React Native
    $validated = $request->validate([
        'addressId'         => 'required',
        'paymentMethod'     => 'required|in:cash,card',
        'deliveryTiming'    => 'required|in:asap,scheduled',
        'deliveryDateTime'  => 'required',
        'cartItems'         => 'required|array',
        'subtotal'          => 'required|integer',
        'deliveryFee'       => 'required|integer',
        'serviceFee'        => 'required|integer',
        'total'             => 'required|integer',
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

        // 2. Save explicitly into the database table
        $order = Order::create([
            'user_id'            => $user->id,
            'address_id'         => $validated['addressId'],
            'payment_method'     => $validated['paymentMethod'],
            'delivery_timing'    => $validated['deliveryTiming'],
            'delivery_date_time' => $validated['deliveryDateTime'],
            'cart_items'         => $validated['cartItems'],
            'subtotal'           => $validated['subtotal'],
            'delivery_fee'       => $validated['deliveryFee'],
            'service_fee'        => $validated['serviceFee'],
            'total'              => $validated['total'],
            'status'             => 'pending'
        ]);

        // 3. Clear out their cart metadata state since order is placed
        $user->cart_meta = [];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Order stored inside DB matrix!',
            'order'   => $order
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