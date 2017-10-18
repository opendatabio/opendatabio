<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Dataset;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatasetPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->access_level >= User::USER;
    }

    public function update(User $user, Dataset $dataset)
    {
        return User::ADMIN == $user->access_level or
            (User::USER == $user->access_level and $dataset->isAdmin($user));
    }
}
