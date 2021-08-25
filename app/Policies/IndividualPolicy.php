<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Individual;
use App\Models\Dataset;
use Illuminate\Auth\Access\HandlesAuthorization;

class IndividualPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the individual.
     */
    public function view(User $user, Individual $individual)
    {
        // is handled by App\Models\Individual::boot globalscope
        return true;
    }

    /**
     * Determine whether the user can create individuals under a given dataset.
     */
    public function create(User $user, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // this policy called with null dataset probably means that we're checking a @can
        if (is_null($dataset)) {
            return User::USER == $user->access_level;
        }
        // for regular users, when actually creating an individual
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }

    /**
     * Determine whether the user can update a individual.
     */
    public function update(User $user, Individual $individual, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        if (is_null($dataset)) {
            $dataset = $individual->dataset;
        }
        // for regular users, when actually creating a voucher
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }

    /**
     * Determine whether the user can delete the individual.
     */
    public function delete(User $user, Individual $individual)
    {
        $has_media = $individual->media()->count()==0;
        $has_measurements = $individual->measurements()->count()==0;
        $has_voucher = $individual->vouchers()->count()==0;
        if (User::ADMIN == $user->access_level) {
            $is_user = true;
        } else {
          $dataset = $individual->dataset;
          $privacy = $dataset->privacy;
          if ($privacy==Dataset::PRIVACY_PROJECT) {
            $is_user = User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
          } else {
            $is_user =  User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
          }
        }
        if ($has_media and $has_measurements and $is_user and $has_voucher) {
          return true;
        }
        return false;
    }

}
