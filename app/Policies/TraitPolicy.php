<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\ODBTrait;

class TraitPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view the odbtrait.
     */
    public function view(User $user, ODBTrait $odbtrait)
    {
	    return true;
    }

    /**
     * Determine whether the user can create odbtraits.
     */
    public function create(User $user)
    {
	    return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can update a odbtrait.
     */
    public function update(User $user, ODBTrait $odbtrait)
    {
        if ($user->access_level == User::ADMIN)
            return true;
        if ($user->access_level == User::USER and $odbtrait->measurements()->count() == 0)
            return true;
        return false;
    }

    /**
     * Determine whether the user can delete the person.
     */
    public function delete(User $user, ODBTrait $odbtrait)
    {
        return false;
    }
}
