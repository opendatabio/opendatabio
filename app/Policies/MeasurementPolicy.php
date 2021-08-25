<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use App\Models\Measurement;
use App\Models\Dataset;

class MeasurementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the measurement.
     */
    public function view(User $user, Measurement $measurement)
    {
        // is handled by App\Models\Measurement::boot globalscope
        return true;
    }

    /**
     * Determine whether the user can create measurements.
     */
    public function create(User $user, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        if (is_null($dataset)) {
            return User::USER == $user->access_level;
        }
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }

    /**
     * Determine whether the user can update a measurement.
     */
    public function update(User $user, Measurement $measurement, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        if (is_null($dataset)) {
            $dataset = $measurement->$dataset;
        }
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }

    /**
     * Determine whether the user can delete the measurement.
     */
    public function delete(User $user, Measurement $measurement)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        $dataset = $measurement->$dataset;
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }
}
