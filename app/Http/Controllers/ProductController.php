<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            return Product::with('shop')->whereHas('shop', function ($q) {
                $q->where('vendor_id', auth()->id());
            })->get();
        } catch (Exception $e) {
            Log::error('Product index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }

        $query = Product::query();

        if ($request->has('sort') && $request->sort === 'created_at_desc') {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('limit')) {
            $query->limit((int) $request->limit);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        Log::info('Product store request:', [
            'data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        try {
            $data = $request->validate([
                'shop_id' => 'required|exists:shops,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0.01',
                'stock' => 'required|integer|min:0',
                'category' => 'required|string|max:255',
                'image' => 'nullable|string'
            ]);

            $shop = Shop::find($data['shop_id']);
            if (!$shop || $shop->vendor_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized or shop not found'], 403);
            }

            $product = Product::create($data);
            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load('shop')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Product store error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create product: ' . $e->getMessage()], 500);
        }
    }

    public function show(Product $product)
    {
        try {
            if ($product->shop->vendor_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return $product->load('shop');
        } catch (Exception $e) {
            Log::error('Product show error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch product'], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            Log::info('Product update attempt', [
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            if ($product->shop->vendor_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0.01',
                'stock' => 'sometimes|required|integer|min:0',
                'category' => 'sometimes|required|string|max:255',
                'image' => 'nullable|string'
            ]);

            Log::info('Validated data for product update', $data);

            $product->update($data);

            Log::info('Product updated successfully', ['product_id' => $product->id]);

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->load('shop')
            ]);
        } catch (Exception $e) {
            Log::error('Product update error: ' . $e->getMessage(), [
                'product_id' => $product->id ?? 'unknown',
                'user_id' => auth()->id() ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->shop->vendor_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $product->delete();

            return response()->json(['message' => 'Product deleted successfully']);
        } catch (Exception $e) {
            Log::error('Product destroy error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }

    public function addProduct(Request $request, Shop $shop)
    {
        if ($shop->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
            'category' => 'required|string|max:255',
            'image' => 'nullable|string'
        ]);

        $data['shop_id'] = $shop->id;
        $product = Product::create($data);

        return response()->json([
            'message' => 'Product added successfully',
            'product' => $product
        ], 201);
    }

    public function updateProduct(Request $request, Shop $shop, Product $product)
    {
        if ($shop->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($product->shop_id !== $shop->id) {
            return response()->json(['message' => 'Product does not belong to this shop'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0.01',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'sometimes|string|max:255',
            'image' => 'nullable|string'
        ]);

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    public function deleteProduct(Shop $shop, Product $product)
    {
        if ($shop->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($product->shop_id !== $shop->id) {
            return response()->json(['message' => 'Product does not belong to this shop'], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function publicIndex()
    {
        try {
            return Product::with('shop')->paginate(20);
        } catch (Exception $e) {
            Log::error('Public product index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    public function latest()
    {
        try {
            $products = Product::with(['shop'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => (float) $product->price,
                        'shop' => $product->shop->name ?? 'Unknown Shop',
                    ];
                });

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch latest products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function publicShow(Product $product)
    {
        try {
            return $product->load('shop');
        } catch (Exception $e) {
            Log::error('Public product show error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch product'], 500);
        }
    }
}
