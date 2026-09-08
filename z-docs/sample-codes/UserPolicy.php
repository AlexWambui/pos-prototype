<?php

namespace App\Policies\Users;

use App\Models\User;
use App\Enums\UserRoles;

class UserPolicy
{
    /**
     * Determine if the user can view any users
     */
    public function viewAny(User $user): bool
    {
        // Super admins and admins can view all users
        return $user->isSuperAdmin() || $user->isAdmin();
    }
    
    /**
     * Determine if the user can view a specific user
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        // Super admins and admins can view any user
        return $user->isSuperAdmin() || $user->isAdmin();
    }
    
    /**
     * Determine if the user can create users
     */
    public function create(User $user): bool
    {
        // Only super admins and admins can create users
        return $user->isSuperAdmin() || $user->isAdmin();
    }
    
    /**
     * Determine if the user can update a user
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        // Super admins can update any user
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Admins can update non-super-admin users
        if ($user->isAdmin() && !$model->isSuperAdmin()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Determine if the user can delete a user
     */
    public function delete(User $user, User $model): bool
    {
        // Only super admins can delete users, and they can't delete themselves
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }
    
    /**
     * Determine if the user can restore a user
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }
    
    /**
     * Determine if the user can change user roles
     */
    public function changeRole(User $user, User $model): bool
    {
        // Only super admins can change roles
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }
}
