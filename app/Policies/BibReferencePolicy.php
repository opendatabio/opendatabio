<?php

namespace App\Policies;

use App\User;
use App\BibReference;
use Illuminate\Auth\Access\HandlesAuthorization;

class BibReferencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the bibReference.
     *
     * @param  \App\User  $user
     * @param  \App\BibReference  $bibReference
     * @return mixed
     */
    public function view(User $user, BibReference $bibReference)
    {
        //
    }

    /**
     * Determine whether the user can create bibReferences.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
	    return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can update the bibReference.
     *
     * @param  \App\User  $user
     * @param  \App\BibReference  $bibReference
     * @return mixed
     */
    public function update(User $user, BibReference $bibReference)
    {
        if ($user->access_level == User::ADMIN) 
            return true;
        // regular users can only update bibreferences that have no associates resources
        if ($user->access_level == User::USER) 
            return (
                $bibReference->taxons()->count() + 
                $bibReference->datasets()->count() + 
                $bibReference->measurements()->count()
            ) == 0;
    }

    /**
     * Determine whether the user can delete the bibReference.
     *
     * @param  \App\User  $user
     * @param  \App\BibReference  $bibReference
     * @return mixed
     */
    public function delete(User $user, BibReference $bibReference)
    {
        // ANY user can only remove bibreferences that have no associates resources
        return (
            $bibReference->taxons()->count() + 
            $bibReference->datasets()->count() + 
            $bibReference->measurements()->count()
        ) == 0;
    }
}
