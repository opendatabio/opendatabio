<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\Measurement;


//TODO!!

class MeasurementPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view the measurement.
     */
    public function view(User $user, Measurement $measurement)
    {
	    return true;
    }

    /**
     * Determine whether the user can create measurements.
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update a measurement.
     */
    public function update(User $user, Measurement $measurement)
    {
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
