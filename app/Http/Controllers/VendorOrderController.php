<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    public function index(Request $request)
    {
        return Order::where('vendor_id', $request->user()->id)
            ->with('product', 'customer')
            ->latest()
            ->get();
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->vendor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,completed,suspended,cancelled'
        ]);

        $order->update(['status' => $data['status']]);

        return response()->json($order);
    }

    public function destroy(Request $request, Order $order)
    {
        if ($order->vendor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
