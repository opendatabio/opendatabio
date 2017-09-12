<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserTranslation;
use App\Language;
use App;

class Tag extends Model
{
    public function translations() {
        return $this->morphMany(UserTranslation::class, 'translatable');
    }
    public function getNameAttribute() {
//        $lid = Language::where('code', '=', App::getLocale());
        $tr =  $this->translations()
            ->join('languages', 'languages.id', '=', 'user_translations.language_id')
            ->where('languages.code', '=', App::getLocale());
        return $tr->first()->translation;
    }
}
