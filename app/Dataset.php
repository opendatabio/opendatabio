<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasAuthLevels;

    // These are the same from Project, but are copied in case we decide to add new levels to either model
    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [Dataset::PRIVACY_AUTH, Dataset::PRIVACY_REGISTERED, Dataset::PRIVACY_PUBLIC];

    protected $fillable = ['name', 'notes', 'privacy'];
/*    public function measurements() {
        return $this->hasMany(Measurement::class);
} */ 
    public function tags() {
        return $this->belongsToMany(Tag::class);
    }
}
