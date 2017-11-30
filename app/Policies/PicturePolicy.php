<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Picture;
use Illuminate\Auth\Access\HandlesAuthorization;

class PicturePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Picture $picture)
    {
        return true;
    }

    public function create(User $user)
    {
        return User::USER <= $user->access_level;
    }

    public function update(User $user, Picture $picture)
    {
        return User::ADMIN == $user->access_level;
    }

    public function delete(User $user, Picture $picture)
    {
        return User::ADMIN == $user->access_level;
    }
}
