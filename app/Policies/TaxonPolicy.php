<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Taxon;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxonPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->access_level >= User::USER;
    }

    public function update(User $user, Taxon $taxon)
    {
        return $user->access_level >= User::USER;
    }
}
