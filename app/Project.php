<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Project extends Model
{
    protected $fillable = ['name', 'notes', 'privacy'];
    public function users() {
        return $this->belongsToMany(User::class)->withPivot('access_level');
    }
    //
}
