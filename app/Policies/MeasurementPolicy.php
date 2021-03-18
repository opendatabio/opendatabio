<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\Measurement;
use App\Dataset;

class MeasurementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the measurement.
     */
    public function view(User $user, Measurement $measurement)
    {
        // is handled by App\Measurement::boot globalscope
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
        // this policy called with null project probably means that we're checking a @can
        if (is_null($dataset)) {
            return User::USER == $user->access_level;
        }
        // for regular users, when actually creating a measurement
        return User::USER == $user->access_level and
            ($dataset->admins->contains($user) or $dataset->users->contains($user));

        return true;
    }

    /**
     * Determine whether the user can update a measurement.
     */
    public function update(User $user, Measurement $measurement)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        $dataset = $measurement->dataset;

        return User::USER == $user->access_level and
            ($dataset->admins->contains($user) or $dataset->users->contains($user));

        return true;
    }

    /**
     * Determine whether the user can delete the person.
     */
    public function delete(User $user, Measurement $measurement)
    {
        return false;
    }
}
