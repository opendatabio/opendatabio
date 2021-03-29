<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Tag $tag)
    {
        return true;
    }

    public function create(User $user)
    {
        return User::ADMIN == $user->access_level;
    }

    public function update(User $user, Tag $tag)
    {
        return User::ADMIN == $user->access_level;
    }

    public function delete(User $user, Tag $tag)
    {
        return User::ADMIN == $user->access_level;
    }
}
