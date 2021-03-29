<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
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

    public function view_details(User $user, Project $project)
    {
        return in_array($user->id,$project->users()->get()->pluck('id')->toArray());
    }

    public function export(User $user, Project $project)
    {
      $valid_users = $project->users()->pluck('users.id')->toArray();
      return User::ADMIN == $user->access_level or in_array($user->id,$valid_users) or $project->privacy >= 1;
    }
}
