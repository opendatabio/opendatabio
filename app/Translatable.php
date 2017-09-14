<?php

namespace App;

use App\UserTranslation;
use App\Language;
use App;

trait Translatable {
    public function translations() {
        return $this->morphMany(UserTranslation::class, 'translatable');
    }
    public function getTranslationAttribute() {
        // tries to get the translation name in the current locale. 
        // if none is found, tries to find the translation name in any language,
        // ordered by language id.
        $tr = $this->translations()
            ->join('languages', 'languages.id', '=', 'user_translations.language_id')
            ->where('languages.code', '=', App::getLocale());
        if ($tr->count())
            return $tr->first()->translation;
        $tr = $this->translations()
            ->orderBy('language_id', 'asc');
        if ($tr->count())
            return $tr->first()->translation;
        // nothing found! Return a warning
        return "Missing translation";
    }
}
