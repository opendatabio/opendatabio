<?php

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
        return $user->access_level == User::ADMIN or 
            ($user->access_level == User::USER and $project->isAdmin($user));
    }

}
