<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/admin/products?search=
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = Product::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('size', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('created_at')->get();

        return response()->json($products->map(fn ($p) => $this->transform($p)));
    }

    /**
     * POST /api/admin/products
     * Covers the marketplace-listing fields (name, description→tagline,
     * size, price, image, available, status) plus the three
     * product-detail-page sections the admin form now also edits:
     * usedFor, specs, regulatory. Everything else the detail page can
     * show (heroDesc, aboutBody, orderTitle, etc.) still isn't part of
     * this form, so those stay on sensible defaults here.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $product = Product::create([
            'name' => $validated['name'],
            'tagline' => $validated['description'] ?: $validated['name'],
            'size' => $validated['size'],
            'price' => (int) round($validated['price']),
            'image_url' => $validated['imageUri'] ?? null,
            'available' => $validated['available'] ?? true,
            'status' => $validated['status'] ?? 'Active',
            'heroDesc' => $validated['description'] ?? '',
            'aboutTitle' => $validated['name'] . ' — In Detail',
            'aboutBody' => $validated['description'] ?? '',
            'bestUsedTitle' => 'Best Used For',
            'usedFor' => $validated['usedFor'] ?? [],
            'specs' => $validated['specs'] ?? [],
            'regulatory' => $validated['regulatory'] ?? [],
            'imageColor' => '#1E5FAF',
            'orderTitle' => 'Order the Kayora ' . $validated['size'] . ' ' . $validated['name'],
            'orderDesc' => $validated['description'] ?? '',
            'is_popular' => false,
        ]);

        return response()->json($this->transform($product), 201);
    }

    /**
     * PUT /api/admin/products/{product}
     * Now also updates usedFor/specs/regulatory since the form edits
     * them — everything else on the detail page (heroDesc, aboutBody,
     * orderTitle, etc.) is still left untouched here.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $this->validatePayload($request);

        $product->update([
            'name' => $validated['name'],
            'tagline' => $validated['description'] ?: $product->tagline,
            'size' => $validated['size'],
            'price' => (int) round($validated['price']),
            'image_url' => $validated['imageUri'] ?? $product->image_url,
            'available' => $validated['available'] ?? $product->available,
            'status' => $validated['status'] ?? $product->status,
            'usedFor' => $validated['usedFor'] ?? $product->usedFor,
            'specs' => $validated['specs'] ?? $product->specs,
            'regulatory' => $validated['regulatory'] ?? $product->regulatory,
        ]);

        return response()->json($this->transform($product));
    }

    /**
     * DELETE /api/admin/products/{product}
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => true]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'size' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'imageUri' => 'nullable|string',
            'available' => 'nullable|boolean',
            'status' => 'nullable|in:Active,Out of Stock,Draft',

            'usedFor' => 'nullable|array',
            'usedFor.*.title' => 'required_with:usedFor.*.description|string|max:255',
            'usedFor.*.description' => 'nullable|string|max:500',

            'specs' => 'nullable|array',
            'specs.*.label' => 'required_with:specs.*.value|string|max:255',
            'specs.*.value' => 'nullable|string|max:255',

            'regulatory' => 'nullable|array',
            'regulatory.*.label' => 'required_with:regulatory.*.value|string|max:255',
            'regulatory.*.value' => 'nullable|string|max:255',
            'regulatory.*.description' => 'nullable|string|max:500',
        ]);
    }

    private function transform(Product $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'description' => $product->tagline,
            'size' => $product->size,
            'price' => (float) $product->price,
            'imageUri' => $product->image_url,
            'available' => (bool) $product->available,
            'status' => $product->status,
            'createdAt' => optional($product->created_at)->toIso8601String() ?? '',
            'usedFor' => is_array($product->usedFor) ? $product->usedFor : [],
            'specs' => is_array($product->specs) ? $product->specs : [],
            'regulatory' => is_array($product->regulatory) ? $product->regulatory : [],
        ];
    }
}