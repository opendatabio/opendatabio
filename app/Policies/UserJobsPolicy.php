<?php

namespace App\Policies;

use App\User;
use App\UserJobs;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserJobsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the userJob.
     *
     * @param  \App\User  $user
     * @param  \App\UserJob  $userJob
     * @return mixed
     */
    public function view(User $user, UserJobs $userJob)
    {
	    //
	    return $user->id == $userJob->user->id;
    }
    public function index(User $user)
    {
	    //
	    return $user->id >= User::REGISTERED;
    }

    /**
     * Determine whether the user can create userJobs.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
	return $user->access_level >= User::REGISTERED;
    }

    /**
     * Determine whether the user can update the userJob.
     *
     * @param  \App\User  $user
     * @param  \App\UserJob  $userJob
     * @return mixed
     */
    public function update(User $user, UserJobs $userJob)
    {
        //
	    return $user->id == $userJob->user->id;
    }

    /**
     * Determine whether the user can delete the userJob.
     *
     * @param  \App\User  $user
     * @param  \App\UserJob  $userJob
     * @return mixed
     */
    public function delete(User $user, UserJobs $userJob)
    {
        //
	    return $user->id == $userJob->user->id;
    }
}
