<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    public function index(Request $request)
    {
        // Check if user can view any orders
        $this->authorize('viewAny', Order::class);
        
        // Get orders for this vendor
        $orders = Order::where('vendor_id', $request->user()->id)
            ->with(['product', 'customer'])
            ->latest()
            ->get();
            
        return response()->json($orders);
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Check if vendor can update this order's status
        $this->authorize('updateStatus', $order);
        
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,completed,cancelled,suspended'
        ]);
        
        $order->update($data);
        
        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->load(['product', 'customer'])
        ]);
    }

    public function destroy(Request $request, Order $order)
    {
        // Check if vendor can delete/cancel this order
        $this->authorize('delete', $order);
        
        // Instead of deleting, mark as cancelled
        $order->update(['status' => 'cancelled']);
        
        return response()->json([
            'message' => 'Order cancelled successfully'
        ]);
    }
}