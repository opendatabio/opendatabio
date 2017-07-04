<?php

namespace App\Policies;

use App\User;
use App\Person;
use Illuminate\Auth\Access\HandlesAuthorization;
use Log;
class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function view(User $user, Person $person)
    {
	Log::info("view??");
        // everyone can view persons
	    return true;
    }

    /**
     * Determine whether the user can create people.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
	    // full users and admins
        return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can update the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function update(User $user, Person $person)
    {
	    // full users and admins
        return $user->access_level >= User::USER;
        //
    }

    /**
     * Determine whether the user can delete the person.
     *
     * @param  \App\User  $user
     * @param  \App\Person  $person
     * @return mixed
     */
    public function delete(User $user, Person $person)
    {
	    // full users and admins
        return $user->access_level >= User::USER;
        //
    }
}
