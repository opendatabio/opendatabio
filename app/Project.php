<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Log;
use App\Plant;

class Project extends Model
{
    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [Project::PRIVACY_AUTH, Project::PRIVACY_REGISTERED, Project::PRIVACY_PUBLIC];

    const VIEWER = 0;
    const COLLABORATOR = 1;
    const ADMIN = 2;

    protected $fillable = ['name', 'notes', 'privacy'];
    public function users() { // all related users
        return $this->belongsToMany(User::class)->withPivot('access_level');
    }
    public function admins() {
        return $this->users()->wherePivot('access_level', '=', Project::ADMIN);
    }
    public function isAdmin(User $user) {
        return in_array($user->id, $this->admins()->pluck('users.id')->all());
    }
    public function collabs() {
        return $this->users()->wherePivot('access_level', '=', Project::COLLABORATOR);
    }
    public function viewers() {
        return $this->users()->wherePivot('access_level', '=', Project::VIEWER);
    }
    public function plants() {
        return $this->hasMany(Plant::class);
    }
    public function setusers(array $new_users = null, $level) {
        // inspired by https://stackoverflow.com/questions/42625797/laravel-sync-only-a-subset-of-the-pivot-table?rq=1
        $current = $this->users->filter(function($users) use ($level) {
            return $users->pivot->access_level === $level;
        })->pluck('id');

        $detach = $current->diff($new_users)->all();

        $attach_ids = collect($new_users)->diff($current)->all();
        $atach_pivot = array_fill(0, count($attach_ids), ['access_level' => $level]);
        $attach = array_combine($attach_ids, $atach_pivot);

        $this->users()->detach($detach);
        $this->users()->attach($attach);

        return $this;

    }
    //
}
