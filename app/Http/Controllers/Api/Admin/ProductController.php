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
     * This form only captures the simple marketplace-listing fields
     * (name, description→tagline, size, price, image, available,
     * status). The richer product-detail-page fields this table also
     * holds (heroDesc, aboutBody, specs, regulatory, etc.) aren't part
     * of this form, so they're filled with sensible defaults here —
     * an admin can flesh those out later via a dedicated detail-page
     * editor if one gets built.
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
            'bestUsedTitle' => '',
            'usedFor' => [],
            'specs' => [],
            'regulatory' => [],
            'imageColor' => '#1E5FAF',
            'orderTitle' => 'Order the Kayora ' . $validated['size'] . ' ' . $validated['name'],
            'orderDesc' => $validated['description'] ?? '',
            'is_popular' => false,
        ]);

        return response()->json($this->transform($product), 201);
    }

    /**
     * PUT /api/admin/products/{product}
     * Only touches the simple admin-facing fields — never overwrites
     * heroDesc/aboutBody/specs/etc, so editing via this form can't
     * silently wipe out existing product-detail-page content.
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
        ];
    }
}