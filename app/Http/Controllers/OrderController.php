<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return Order::where('customer_id', $request->user()->id)
            ->with('product')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'message' => 'nullable|string'
        ]);

        $product = Product::with('shop')->findOrFail($data['product_id']);
        
        $customerId = $request->user()->id;

        $order = Order::create([
            ...$data,
            'vendor_id' => $product->shop->vendor_id,
            'customer_id' => $customerId,
            'status' => 'pending'
        ]);

        return response()->json($order->load('product'), 201);
    }


    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'message' => 'nullable|string',
        ]);

        $order->update($data);

        return response()->json($order);
    }

    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);
        
        $order->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Order cancelled successfully']);
    }
}