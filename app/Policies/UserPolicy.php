<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can show user details
     * NOTE non-standard policy name
     *
     * @param  \App\User  $user
     * @param  \App\User  $user
     * @return mixed
     */
    public function show(User $user)
    {
	return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can create users.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
	return false;
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param  \App\User  $user
     * @param  \App\User  $user
     * @return mixed
     */
    public function update(User $user, User $object)
    {
	return $user->access_level == User::ADMIN;
    }

    /**
     * Determine whether the user can delete the user.
     *
     * @param  \App\User  $user
     * @param  \App\User  $user
     * @return mixed
     */
    public function delete(User $user, User $object)
    {
	return $user->access_level == User::ADMIN;
    }
}
