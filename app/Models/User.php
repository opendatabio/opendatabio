<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Lang;

class User extends Authenticatable
{
    use Notifiable;

    // Access levels
    const REGISTERED = 0;
    const USER = 1;
    const ADMIN = 2;
    const LEVELS = [self::REGISTERED, self::USER, self::ADMIN];

    protected $fillable = ['email', 'password', 'person_id', 'project_id', 'dataset_id'];
    protected $hidden = ['password', 'remember_token', 'api_token'];

    public function rawLink()
    {
        return '<a href="'.url('users/'.$this->id).'">'.
            // Needs to escape special chars, as this will be passed RAW
            htmlspecialchars($this->email).'</a>';
    }

    public function identifiableName()
    {
        return $this->person->fullname;
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('email', 'asc');
        });
    }

    public function setToken()
    {
        $this->api_token = substr(bcrypt($this->email.date('YmdHis').config('app.key')), 8, 12);
        $this->save();
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    // NOTE, these functions should never be used to VALIDATE authorization, only to display a default in forms.
    // Use $user->projects and $user->datasets for authorization
    public function defaultDataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id');
    }

    public function defaultProject()
    {
        return $this->belongsTo(Project::class, 'project_id');
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

    public function editableDatasets()
    {
        $datasets = Dataset::leftJoin('dataset_user','datasets.id','dataset_id')->leftJoin('project_user','datasets.project_id','project_user.project_id');
        $datasets = $datasets->whereRaw("(dataset_user.access_level > ".Project::VIEWER." AND dataset_user.user_id=".$this->id.") OR (project_user.access_level >".Project::VIEWER." AND project_user.user_id=".$this->id." AND datasets.privacy=".Dataset::PRIVACY_PROJECT.")");
        return $datasets;
    }

    public function forms()
    {
        return $this->hasMany(Form::class);
    }
}
