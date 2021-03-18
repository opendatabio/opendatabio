<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Biocollection;
use Illuminate\Auth\Access\HandlesAuthorization;

class BiocollectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the biocollection.
     *
     * @param \App\User      $user
     * @param \App\Biocollection $biocollection
     *
     * @return mixed
     */
    public function view(User $user, Biocollection $biocollection)
    {
        // everyone can view biocollections
        return true;
    }

    /**
     * Determine whether the user can create biocollections.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return User::ADMIN == $user->access_level;
    }

    /**
     * Determine whether the user can update the biocollection.
     *
     * @param \App\User      $user
     * @param \App\Biocollection $biocollection
     *
     * @return mixed
     */
    public function update(User $user, Biocollection $biocollection)
    {
        // Currently impossible!
        return false;
    }

    /**
     * Determine whether the user can delete the biocollection.
     *
     * @param \App\User      $user
     * @param \App\Biocollection $biocollection
     *
     * @return mixed
     */
    public function delete(User $user, Biocollection $biocollection)
    {
        return User::ADMIN == $user->access_level;
    }
}
