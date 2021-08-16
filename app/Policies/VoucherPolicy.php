<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;
use App\Models\Dataset;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the voucher.
     */
    public function view(User $user, Voucher $voucher)
    {
        // is handled by App\Models\Voucher::boot globalscope
        return true;
    }

    /**
     * Determine whether the user can create vouchers under a given dataset.
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
        // for regular users, when actually creating a voucher
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }

    /**
     * Determine whether the user can update a voucher.
     */
    public function update(User $user, Voucher $voucher, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        if (is_null($dataset)) {
            $dataset = $voucher->dataset;
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
     * Determine whether the user can delete the vocuher.
     */
    public function delete(User $user, Voucher $voucher)
    {
        $has_media = $voucher->media()->count()==0;
        $has_measurements = $voucher->measurements()->count()==0;
        if (User::ADMIN == $user->access_level) {
            $is_user = true;
        } else {
          $dataset = $voucher->dataset;
          $privacy = $dataset->privacy;
          if ($privacy==Dataset::PRIVACY_PROJECT) {
            $is_user = User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
          } else {
            $is_user =  User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
          }
        }
        if ($has_media and $has_measurements and $is_user) {
          return true;
        }
        return false;
    }
}
