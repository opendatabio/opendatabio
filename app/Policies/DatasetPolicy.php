<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

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

    // TODO: shouldn collaborators also allowed to update datasets? else what are collabs for?
    public function update(User $user, Dataset $dataset)
    {
        return User::ADMIN == $user->access_level or
            (User::USER == $user->access_level and $dataset->isAdmin($user));
    }

    public function export(User $user, Dataset $dataset)
    {
      $valid_users = $dataset->users()->pluck('users.id')->toArray();
      return User::ADMIN == $user->access_level or in_array($user->id,$valid_users) or $dataset->privacy > 0;
    }
}
