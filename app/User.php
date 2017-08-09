<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Lang;
use App\Project;

class User extends Authenticatable
{
    use Notifiable;

    // Access levels
    const REGISTERED = 0;
    const USER = 1;
    const ADMIN = 2;
    const LEVELS = [User::REGISTERED, User::USER, User::ADMIN];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password','person_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    public function userjobs()
    {
        return $this->hasMany('App\UserJobs');
    }
    public function getTextAccessAttribute() {
	    return Lang::get('levels.access.' . $this->access_level);
    }
    public function projects() {
        return $this->belongsToMany(Project::class)->withPivot('access_level');
    }
}
