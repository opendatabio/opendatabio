<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTranslation extends Model
{
    const NAME = 0;
    const DESCRIPTION = 1;

    protected $fillable = ['translatable_type', 'translatable_id', 'language_id', 'translation', 'translation_type'];

    // Always eager loads the "language" relation
    protected $with = ['language'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function translatable()
    {
        return $this->morphTo();
    }
}
