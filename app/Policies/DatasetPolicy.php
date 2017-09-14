<?php

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
        return $user->access_level == User::ADMIN or 
            ($user->access_level == User::USER and $dataset->isAdmin($user));
    }

}
