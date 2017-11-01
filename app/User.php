<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Lang;

class User extends Authenticatable
{
    use Notifiable;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('email', 'asc');
        });
    }

    // Access levels
    const REGISTERED = 0;
    const USER = 1;
    const ADMIN = 2;
    const LEVELS = [self::REGISTERED, self::USER, self::ADMIN];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'person_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token',
    ];

    public function setToken()
    {
        $this->api_token = substr(bcrypt($this->email.date('YmdHis').config('app.key')), 8, 12);
        $this->save();
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function userjobs()
    {
        return $this->hasMany(UserJob::class);
    }

    public function getTextAccessAttribute()
    {
        return Lang::get('levels.access.'.$this->access_level);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class)->withPivot('access_level');
    }

    public function datasets()
    {
        return $this->belongsToMany(Dataset::class)->withPivot('access_level');
    }
}
