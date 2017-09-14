<?php

namespace App\Policies;

use App\User;
use App\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Tag $tag)
    {
	return true;
    }

    public function create(User $user)
    {
	    return $user->access_level == User::ADMIN;
    }

    public function update(User $user, Tag $tag)
    {
	    return $user->access_level == User::ADMIN;
    }

    public function delete(User $user, Tag $tag)
    {
	    return $user->access_level == User::ADMIN;
    }
}
