<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can show user details
     * NOTE non-standard policy name.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $user
     *
     * @return mixed
     */
    public function show(User $user)
    {
        return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can create users.
     *
     * @param \App\Models\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $user
     *
     * @return mixed
     */
    public function update(User $user, User $object)
    {
        return User::ADMIN == $user->access_level;
    }

    /**
     * Determine whether the user can delete the user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $user
     *
     * @return mixed
     */
    public function delete(User $user, User $object)
    {
        return User::ADMIN == $user->access_level;
    }
}
