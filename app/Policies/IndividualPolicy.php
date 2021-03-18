<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Individual;
use App\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class IndividualPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the individual.
     */
    public function view(User $user, Individual $individual)
    {
        // is handled by App\Individual::boot globalscope
        return true;
    }

    /**
     * Determine whether the user can create individuals under a given project.
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
        // for regular users, when actually creating an individual
        return User::USER == $user->access_level and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can update a individual.
     */
    public function update(User $user, Individual $individual)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        $project = $individual->project;

        return User::USER == $user->access_level and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can delete the individual.
     */
    public function delete(User $user, Individual $individual)
    {
        return false;
    }
}
