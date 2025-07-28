<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
{
    public function index()
    {
        try {
            $shops = Shop::where('vendor_id', auth()->id())
                ->with(['products' => function($query) {
                    $query->select('id', 'shop_id', 'name', 'price', 'stock');
                }])
                ->get();

            return response()->json([
                'success' => true,
                'shops' => $shops
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Accept image files
                'thumbnail' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('shop-images', 'public');
            }

            $shop = Shop::create([
                'vendor_id' => auth()->id(),
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'image' => $imagePath, // Store the file path
                'thumbnail' => $request->thumbnail,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shop created successfully',
                'shop' => $shop
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Shop $shop)
    {
        try {
            if ($shop->vendor_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $shop->load('products');

            return response()->json([
                'success' => true,
                'shop' => $shop
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Shop $shop)
    {
        try {
            if ($shop->vendor_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'category' => 'sometimes|required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'thumbnail' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['name', 'category', 'description']);

            // Handle image upload for update
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($shop->image && Storage::disk('public')->exists($shop->image)) {
                    Storage::disk('public')->delete($shop->image);
                }
                
                $updateData['image'] = $request->file('image')->store('shop-images', 'public');
            }

            $shop->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Shop updated successfully',
                'shop' => $shop->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProducts(Shop $shop)
    {
        try {
            if ($shop->vendor_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $products = $shop->products()->get();
            
            return response()->json([
                'products' => $products,
                'shop' => $shop
            ]);
        } catch (Exception $e) {
            Log::error('Get shop products error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    public function destroy(Shop $shop)
    {
        try {
            if ($shop->vendor_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Delete associated image
            if ($shop->image && Storage::disk('public')->exists($shop->image)) {
                Storage::disk('public')->delete($shop->image);
            }

            $shop->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shop deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function publicIndex()
    {
        try {
            $shops = Shop::with(['products' => function($query) {
                $query->select('id', 'shop_id', 'name', 'price', 'image');
            }])->get();

            return response()->json([
                'success' => true,
                'shops' => $shops
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function publicShow(Shop $shop)
    {
        try {
            $shop->load('products');

            return response()->json([
                'success' => true,
                'shop' => $shop
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shop',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addProduct(Request $request, Shop $shop)
    {
        try {
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
        } catch (Exception $e) {
            Log::error('Add product to shop error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add product: ' . $e->getMessage()], 500);
        }
    }

    public function updateProduct(Request $request, Shop $shop, Product $product)
    {
        try {
            Log::info('Shop product update attempt', [
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            if ($shop->vendor_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($product->shop_id !== $shop->id) {
                return response()->json(['message' => 'Product does not belong to this shop'], 403);
            }

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0.01',
                'stock' => 'sometimes|required|integer|min:0',
                'category' => 'sometimes|required|string|max:255',
                'image' => 'nullable|string'
            ]);

            Log::info('Validated data for shop product update', $data);

            $product->update($data);
            
            Log::info('Shop product updated successfully', [
                'shop_id' => $shop->id,
                'product_id' => $product->id
            ]);
            
            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);
        } catch (Exception $e) {
            Log::error('Shop product update error: ' . $e->getMessage(), [
                'shop_id' => $shop->id ?? 'unknown',
                'product_id' => $product->id ?? 'unknown',
                'user_id' => auth()->id() ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteProduct(Shop $shop, Product $product)
    {
        try {
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
        } catch (Exception $e) {
            Log::error('Delete shop product error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }
}