<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Lang;
use App\User;

class Dataset extends Model
{
    use HasAuthLevels;

    // These are the same from Project, but are copied in case we decide to add new levels to either model
    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [self::PRIVACY_AUTH, self::PRIVACY_REGISTERED, self::PRIVACY_PUBLIC];

    protected $fillable = ['name', 'notes', 'privacy', 'bibreference_id'];

    public function rawLink()
    {
        return "<a href='".url('datasets/'.$this->id)."'>".htmlspecialchars($this->name).'</a>';
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function getTagLinksAttribute()
    {
        if (empty($this->tags)) {
            return '';
        }
        $ret = '';
        foreach ($this->tags as $tag) {
            $ret .= "<a href='".url('tags/'.$tag->id)."'>".htmlspecialchars($tag->name).'</a><br>';
        }

        return $ret;
    }

    public function getTaggedWidthAttribute()
    {
        if (empty($this->tags)) {
            return '';
        }
        $ret = '';
        foreach ($this->tags as $tag) {
            $ret .= $tag->name;
        }

        return $ret;
    }

    public function reference()
    {
        return $this->belongsTo(BibReference::class, 'bibreference_id');
    }

    // for use in the trait edit dropdown
    public function getPrivacyLevelAttribute()
    {
        return Lang::get('levels.privacy.'.$this->privacy);
    }

    public function getContactEmailAttribute()
    {
        return $this->users()->wherePivot('access_level', '=', User::ADMIN)->first()->email;
    }
}
