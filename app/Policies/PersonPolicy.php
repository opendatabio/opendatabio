<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Person;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the person.
     *
     * @param \App\User   $user
     * @param \App\Person $person
     *
     * @return mixed
     */
    public function view(User $user, Person $person)
    {
        // everyone can view persons
        return true;
    }

    /**
     * Determine whether the user can create people.
     *
     * @param \App\User $user
     *
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
     * @param \App\User   $user
     * @param \App\Person $person
     *
     * @return mixed
     */
    public function update(User $user, Person $person)
    {
        //if person is set as the default person of a user it can only be updated by the user or and admin user
        $userpersons = User::where('id','<>',$user->id)->whereRaw('person_id IS NOT NULL')->pluck('person_id')->toArray();

        // full users and admins
        return $user->access_level == User::ADMIN  or  !in_array($person->id,$userpersons);
    }

    /**
     * Determine whether the user can delete the person.
     *
     * @param \App\User   $user
     * @param \App\Person $person
     *
     * @return mixed
     */
    public function delete(User $user, Person $person)
    {
        // full users and admins
        return $user->access_level >= User::USER;
    }
}
