<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        // Both customers and vendors can view orders
        return in_array($user->role, ['customer', 'vendor']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order)
    {
        // Customer can view their own orders
        if ($user->role === 'customer') {
            return $user->id === $order->customer_id;
        }
        
        // Vendor can view orders for their products
        if ($user->role === 'vendor') {
            return $user->id === $order->vendor_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        // Only customers can create orders
        return $user->role === 'customer';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order)
    {
        // Customer can only update their own orders (quantity, message)
        if ($user->role === 'customer') {
            return $user->id === $order->customer_id && 
                   in_array($order->status, ['pending', 'confirmed']);
        }
        
        // Vendor can update order status
        if ($user->role === 'vendor') {
            return $user->id === $order->vendor_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order)
    {
        // Customer can cancel their own orders that aren't completed
        if ($user->role === 'customer') {
            return $user->id === $order->customer_id && 
                   !in_array($order->status, ['completed', 'cancelled']);
        }
        
        // Vendor can cancel/reject orders
        if ($user->role === 'vendor') {
            return $user->id === $order->vendor_id && 
                   !in_array($order->status, ['completed', 'shipped']);
        }
        
        return false;
    }

    /**
     * Determine whether the vendor can update order status.
     */
    public function updateStatus(User $user, Order $order)
    {
        // Only vendors can update order status
        return $user->role === 'vendor' && $user->id === $order->vendor_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order)
    {
        // Both customer and vendor can restore cancelled orders
        return ($user->id === $order->customer_id && $user->role === 'customer') ||
               ($user->id === $order->vendor_id && $user->role === 'vendor');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order)
    {
        // Only super admin or the vendor can permanently delete
        return $user->role === 'super_admin' || 
               ($user->role === 'vendor' && $user->id === $order->vendor_id);
    }
}