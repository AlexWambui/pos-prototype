<?php

namespace App\Policies\Branches;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        // All authenticated users can view branches
        return true;
    }
    
    public function create(User $user): bool
    {
        // Only admins can create branches
        return $user->isSuperAdmin() || $user->isAdmin();
    }
    
    public function update(User $user, Branch $branch): bool
    {
        // Only admins can update branches
        return $user->isSuperAdmin() || $user->isAdmin();
    }
    
    public function delete(User $user, Branch $branch): bool
    {
        // Only super admins can delete branches
        return $user->isSuperAdmin();
    }
}
