<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Plant;
use App\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the plant.
     */
    public function view(User $user, Plant $person)
    {
        // is handled by App\Plant::boot globalscope
        return true;
    }

    /**
     * Determine whether the user can create plants under a given project.
     */
    public function create(User $user, Project $project = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // this policy called with null project probably means that we're checking a @can
        if (is_null($project)) {
            return User::USER == $user->access_level;
        }
        // for regular users, when actually creating a plant
        return User::USER == $user->access_level and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can update a plant.
     */
    public function update(User $user, Plant $plant)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        $project = $plant->project;

        return User::USER == $user->access_level and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can delete the person.
     */
    public function delete(User $user, Plant $plant)
    {
        return false;
    }
}
