<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use DB;

// This is very hackish, but a trait cannot have constants, so we define the consts in App\Models\Project and
// reference them as Project::ADMIN, Project::COLLABORATOR and Project::ADMIN for all objects that can
// have auth levels.
trait HasAuthLevels
{
    public function users()
    { // all related users
        return $this->belongsToMany(User::class)->withPivot('access_level');
    }

    public function admins()
    {
        return $this->users()->wherePivot('access_level', '=', Project::ADMIN);
    }

    public function isAdmin(User $user)
    {
        return in_array($user->id, $this->admins()->pluck('users.id')->all());
    }

    public function collabs()
    {
        return $this->users()->wherePivot('access_level', '=', Project::COLLABORATOR);
    }

    public function viewers()
    {
        return $this->users()->wherePivot('access_level', '=', Project::VIEWER);
    }

    public function setusers($viewers, $collabs, $admins)
    {
        // removes duplicates, keeping the higher permission
        $viewers = collect($viewers)->diff($collabs)->diff($admins)->all();
        $collabs = collect($collabs)->diff($admins)->all();

        DB::transaction(function () use ($admins, $collabs, $viewers) {
            $this->users()->detach();
            $this->setusers_level($admins, Project::ADMIN);
            $this->setusers_level($collabs, Project::COLLABORATOR);
            $this->setusers_level($viewers, Project::VIEWER);
        });
    }

    protected function setusers_level(array $new_users = null, $level)
    {
        $atach_pivot = array_fill(0, count($new_users), ['access_level' => $level]);
        $attach = array_combine($new_users, $atach_pivot);
        $this->users()->attach($attach);
    }
}
