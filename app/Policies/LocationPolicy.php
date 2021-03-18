<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\User;
use App\Location;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the location.
     *
     * @param \App\User     $user
     * @param \App\Location $location
     *
     * @return mixed
     */
    public function view(User $user, Location $location)
    {
    }

    /**
     * Determine whether the user can create locations.
     *
     * @param \App\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can update the location.
     *
     * @param \App\User     $user
     * @param \App\Location $location
     *
     * @return mixed
     */
    public function update(User $user, Location $location)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        if (User::USER == $user->access_level) {
            $m = $location->measurements()->withoutGlobalScopes()->get()->count();
            $p = $location->individuals()->withoutGlobalScopes()->get()->count();
            $v = $location->vouchers()->withoutGlobalScopes()->get()->count();

            return 0 == $m + $p + $v;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the location.
     *
     * @param \App\User     $user
     * @param \App\Location $location
     *
     * @return mixed
     */
    public function delete(User $user, Location $location)
    {
        if (($user->access_level >= User::USER) and
            (Location::LEVEL_PLOT == $location->adm_level or Location::LEVEL_POINT == $location->adm_level)) {
            $m = $location->measurements()->withoutGlobalScopes()->get()->count();
            $p = $location->individuals()->withoutGlobalScopes()->get()->count();
            $v = $location->vouchers()->withoutGlobalScopes()->get()->count();

            return 0 == $m + $p + $v;
        }
    }
}
