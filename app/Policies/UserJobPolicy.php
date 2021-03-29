<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\UserJob;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserJobPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the userJob.
     *
     * @param \App\Models\User    $user
     * @param \App\Models\UserJob $userJob
     *
     * @return mixed
     */
    public function view(User $user, UserJob $userJob)
    {
        return $user->id == $userJob->user->id;
    }

    public function index(User $user)
    {
        return $user->id >= User::REGISTERED;
    }

    /**
     * Determine whether the user can create userJobs.
     *
     * @param \App\Models\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_level >= User::REGISTERED;
    }

    /**
     * Determine whether the user can update the userJob.
     *
     * @param \App\Models\User    $user
     * @param \App\Models\UserJob $userJob
     *
     * @return mixed
     */
    public function update(User $user, UserJob $userJob)
    {
        return $user->id == $userJob->user->id;
    }

    /**
     * Determine whether the user can delete the userJob.
     *
     * @param \App\Models\User    $user
     * @param \App\Models\UserJob $userJob
     *
     * @return mixed
     */
    public function delete(User $user, UserJob $userJob)
    {
        return $user->id == $userJob->user->id;
    }
}
