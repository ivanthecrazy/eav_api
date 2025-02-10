<?php
namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;

class AttributePolicy
{
    /**
     * Determine if the user can view attributes.
     */
    public function viewAny(User $user): bool
    {
        return true; // Allow all authenticated users to list attributes
    }

    /**
     * Determine if the user can view a specific attribute.
     */
    public function view(User $user, Attribute $attribute): bool
    {
        return true; // Allow all authenticated users to view attributes
    }

    /**
     * Determine if the user can create an attribute.
     */
    public function create(User $user): bool
    {
        return true; // Allow all authenticated users to create attributes
    }

    /**
     * Determine if the user can update an attribute.
     */
    public function update(User $user, Attribute $attribute): bool
    {
        return true; // Allow all authenticated users to update attributes
    }

    /**
     * Determine if the user can delete an attribute.
     */
    public function delete(User $user, Attribute $attribute): bool
    {
        return true; // Allow all authenticated users to delete attributes
    }
}