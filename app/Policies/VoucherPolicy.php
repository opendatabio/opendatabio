<?php

namespace App\Policies;

use App\User;
use App\Voucher;
use App\Project;
use Illuminate\Auth\Access\HandlesAuthorization;
use Log;

class VoucherPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view the voucher.
     */
    public function view(User $user, Voucher $voucher)
    {
        // is handled by App\Voucher::boot globalscope
	    return true;
    }

    /**
     * Determine whether the user can create vouchers under a given project.
     */
    public function create(User $user, Project $project = null)
    {
        if ($user->access_level == User::ADMIN) return true;
        // this policy called with null project probably means that we're checking a @can
        if (is_null($project)) return $user->access_level == User::USER;
	    // for regular users, when actually creating a voucher
        return $user->access_level == User::USER and
            ($project->admins->contains($user) or $project->users->contains($user));
    }

    /**
     * Determine whether the user can update a voucher.
     */
    public function update(User $user, Voucher $voucher)
    {
        if ($user->access_level == User::ADMIN) return true;
	    // for regular users
        $project = $voucher->project;
        return $user->access_level == User::USER and
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
