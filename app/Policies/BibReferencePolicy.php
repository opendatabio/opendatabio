<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\BibReference;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibReferencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the bibReference.
     *
     * @param \App\Models\User         $user
     * @param \App\Models\BibReference $bibReference
     *
     * @return mixed
     */
    public function view(User $user, BibReference $bibReference)
    {
    }

    /**
     * Determine whether the user can create bibReferences.
     *
     * @param \App\Models\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can update the bibReference.
     *
     * @param \App\Models\User         $user
     * @param \App\Models\BibReference $bibReference
     *
     * @return mixed
     */
    public function update(User $user, BibReference $bibReference)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // regular users can only update bibreferences that have no associates resources
        if (User::USER == $user->access_level) {
            return 0 == (
                $bibReference->taxons()->count() +
                $bibReference->datasets()->count() +
                $bibReference->measurements()->count()
            );
        }
    }

    /**
     * Determine whether the user can delete the bibReference.
     *
     * @param \App\Models\User         $user
     * @param \App\Models\BibReference $bibReference
     *
     * @return mixed
     */
    public function delete(User $user, BibReference $bibReference)
    {
        // ANY user can only remove bibreferences that have no associates resources
        return 0 == (
            $bibReference->taxons()->count() +
            $bibReference->datasets()->count() +
            $bibReference->measurements()->count()
        );
    }
}
