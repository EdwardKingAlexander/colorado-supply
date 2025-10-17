<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'sales_rep']);
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Super admins and admins can view all
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        // Sales reps can only view their own orders
        return $order->created_by === $user->id;
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'sales_rep']);
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Super admins and admins can update all
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        // Sales reps can only update their own unpaid orders
        return $order->created_by === $user->id && ! $order->isPaid();
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only super admins and admins can delete
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return false;
        }

        // Cannot delete paid orders
        return ! $order->isPaid();
    }

    /**
     * Determine if the user can manually mark order as paid.
     */
    public function markPaid(User $user, Order $order): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
