<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->access_level >= User::USER;
    }

    public function update(User $user, Project $project)
    {
        return User::ADMIN == $user->access_level or
            (User::USER == $user->access_level and $project->isAdmin($user));
    }
}
