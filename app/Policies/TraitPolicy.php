<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\ODBTrait;


//TODO!!

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
        return true;
    }

    /**
     * Determine whether the user can update a odbtrait.
     */
    public function update(User $user, ODBTrait $odbtrait)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the person.
     */
    public function delete(User $user, ODBTrait $odbtrait)
    {
        return false;
    }
}
