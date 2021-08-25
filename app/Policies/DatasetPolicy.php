<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Dataset;
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
        $has_admin = $dataset->admins()->count();
        if ($has_admin) {
          $is_admin = $dataset->isAdmin($user);
        } else {
          $is_admin = $dataset->project->isAdmin($user);
        }
        return User::ADMIN == $user->access_level or
            (User::USER == $user->access_level and $is_admin);
    }


    public function export(User $user, Dataset $dataset)
    {
      //direct users or project users
      $is_open = in_array($dataset->privacy,[Dataset::PRIVACY_REGISTERED,Dataset::PRIVACY_PUBLIC]);
      if ($dataset->privacy==Dataset::PRIVACY_PROJECT) {
        $valid_users = $dataset->project->users()->pluck('users.id')->toArray();
      } else {
        $valid_users = $dataset->users()->pluck('users.id')->toArray();
      }
      return (User::ADMIN == $user->access_level) or in_array($user->id,$valid_users) or $is_open;
    }




}
