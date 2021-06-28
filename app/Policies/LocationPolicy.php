<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Location;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the location.
     *
     * @param \App\Models\User     $user
     * @param \App\Models\Location $location
     *
     * @return mixed
     */
    public function view(User $user, Location $location)
    {
    }

    /**
     * Determine whether the user can create locations.
     *
     * @param \App\Models\User $user
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
     * @param \App\Models\User     $user
     * @param \App\Models\Location $location
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
            $media = $location->media()->withoutGlobalScopes()->count();
            return 0 == $m + $p + $v + $media;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the location.
     *
     * @param \App\Models\User     $user
     * @param \App\Models\Location $location
     *
     * @return mixed
     */
    public function delete(User $user, Location $location)
    {
        /* any full user can delete if there is no data associated with the location
        * nor the location has children
        */
        if ($user->access_level >= User::USER) {
        //and (Location::LEVEL_PLOT == $location->adm_level or Location::LEVEL_POINT == $location->adm_level)) {
            $m = $location->measurements()->withoutGlobalScopes()->count();
            $p = $location->individuals()->withoutGlobalScopes()->count();
            $v = $location->vouchers()->withoutGlobalScopes()->count();
            $media = $location->media()->withoutGlobalScopes()->count();
            $children = $location->getDescendants()->count();
            return 0 == ($m + $p + $v + $media + $children);
        }

        return false;
    }
}
