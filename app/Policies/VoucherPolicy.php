<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;
use App\Models\Project;
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
     * Determine whether the user can create vouchers under a given project.
     */
    public function create(User $user, Project $project = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // this policy called with null project probably means that we're checking a @can
        if (is_null($project)) {
            return User::USER == $user->access_level;
        }
        // for regular users, when actually creating a voucher
        return User::USER == $user->access_level and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can update a voucher.
     */
    public function update(User $user, Voucher $voucher)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // for regular users
        $project = $voucher->project;

        return User::USER == $user->access_level and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can delete the person.
     */
    public function delete(User $user, Voucher $voucher)
    {
        return false;
    }
}
