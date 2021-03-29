<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Support\Facades\App;

trait Translatable
{
    public function translations()
    {
        return $this->morphMany(UserTranslation::class, 'translatable');
    }

    // For usual access, use $obj->name or $obj->description
    // NOTE: as this is used in displays, returns a warning if no translation is found
    protected function findAttribute($which)
    {
        // tries to get the translation name in the current locale.
        // if none is found, tries to find the translation name in any language,
        // ordered by language id.
        $tr = $this->translations->filter(function ($tr) use ($which) {
            return $tr->translation_type == $which and $tr->language->code == App::getLocale();
        });
        if ($tr->count()) {
            return $tr->first()->translation;
        }
        $tr = $this->translations->filter(function ($tr) use ($which) {
            return $tr->translation_type == $which;
        })->sortBy('language_id');
        if ($tr->count()) {
            return $tr->first()->translation;
        }
        // nothing found! Return a warning
        return 'Missing translation';
    }

    public function getNameAttribute()
    {
        return $this->findAttribute(UserTranslation::NAME);
    }

    public function getDescriptionAttribute()
    {
        return $this->findAttribute(UserTranslation::DESCRIPTION);
    }

    // for use in forms, get the translation in a specified language
    // NOTE: as this is used in forms, missing translations return blank (null)
    public function translate($which, $lang)
    {
        $ret = $this->translations->filter(function ($tr) use ($which, $lang) {
            return $tr->language_id == $lang and $tr->translation_type == $which;
        });
        if ($ret->count()) {
            return $ret->first()->translation;
        }

        return null;
    }

    // for use in Controllers
    public function setTranslation($which, $lang, $translation)
    {
        if ($translation and $this->translate($which, $lang)) {
            $this->translations()
                ->where('language_id', '=', $lang)
                ->where('translation_type', '=', $which)
                ->update(['translation' => $translation]);
        } elseif ($translation) {
            $this->translations()
                ->save(new UserTranslation([
                    'language_id' => $lang,
                    'translation_type' => $which,
                    'translation' => $translation,
                ]));
        } else {
            $this->translations()
                ->where('language_id', '=', $lang)
                ->where('translation_type', '=', $which)
                ->delete();
        }
    }
}
