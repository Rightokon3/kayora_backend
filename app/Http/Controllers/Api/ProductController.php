<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get the currently logged-in user (if any) via Sanctum
        $user = Auth::guard('sanctum')->user();
        $isDistributor = ($user && $user->account_type === 'distributor');

        $query = Product::query();

        // Server-Side Search Engine implementation matching React Native attributes
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = trim(strtolower($request->search));
            
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(size) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(tagline) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        $products = $query->get();

        // 2. Map through products to apply tiered pricing dynamically
        $processedProducts = $products->map(function($product) use ($isDistributor) {
            // Convert product model to array to safely overwrite fields
            $productArray = $product->toArray();
            
            // Set price dynamically based on account type tier
            $productArray['price'] = $isDistributor 
                ? (int)($product->distributor_price_kobo ?? $product->price) 
                : (int)$product->price;

            $productArray['is_distributor_price'] = $isDistributor;

            return $productArray;
        });

        return response()->json([
            'success' => true,
            'products' => $processedProducts
        ]);
    }

    public function show($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // 1. Check user context for single detailed product page pricing
            $user = Auth::guard('sanctum')->user();
            $isDistributor = ($user && $user->account_type === 'distributor');

            // 2. Choose price field tier mapping
            $finalPrice = $isDistributor 
                ? (int)($product->distributor_price_kobo ?? $product->price) 
                : (int)$product->price;

            // Bulletproof defaults if your DB columns are empty or null
            return response()->json([
                'id'             => (int)$product->id,
                'name'           => (string)$product->name,
                'size'           => (string)$product->size,
                'tagline'        => (string)($product->tagline ?? ''),
                'price'          => $finalPrice, // <-- Dynamic Tiered Price
                'is_distributor_price' => $isDistributor,
                'heroDesc'       => (string)($product->heroDesc ?? ''),
                'aboutTitle'     => (string)($product->aboutTitle ?? 'About Product'),
                'aboutBody'      => (string)($product->aboutBody ?? ''),
                'bestUsedTitle'  => (string)($product->bestUsedTitle ?? 'Best Used For'),
                'usedFor'        => is_array($product->usedFor) ? $product->usedFor : [],
                'specs'          => is_array($product->specs) ? $product->specs : [],
                'regulatory'     => is_array($product->regulatory) ? $product->regulatory : [],
                'imageColor'     => (string)($product->imageColor ?? '#1E5FAF'),
                'orderTitle'     => (string)($product->orderTitle ?? 'Order Now'),
                'orderDesc'      => (string)($product->orderDesc ?? ''),
            ]);

        } catch (\Exception $e) {
            // This will print the exact line causing the 500 error into your Laravel logs!
            \Log::error('Product show crash: ' . $e->getMessage());
            return response()->json([
                'error' => 'Server crashed internally',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}