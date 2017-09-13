<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Log;
use DB;
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
    public function vouchers() {
        return $this->hasMany(Voucher::class);
    }
    public function setusers($viewers, $collabs, $admins) {
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
    protected function setusers_level(array $new_users = null, $level) {
        $atach_pivot = array_fill(0, count($new_users), ['access_level' => $level]);
        $attach = array_combine($new_users, $atach_pivot);
        $this->users()->attach($attach);
    }
}
