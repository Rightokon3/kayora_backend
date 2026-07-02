<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
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

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
    public function show($id)
{
    try {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Bulletproof defaults if your DB columns are empty or null
        return response()->json([
            'id'             => (int)$product->id,
            'name'           => (string)$product->name,
            'size'           => (string)$product->size,
            'tagline'        => (string)($product->tagline ?? ''),
            'price'          => (int)$product->price,
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