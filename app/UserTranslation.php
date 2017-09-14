<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Language;

class UserTranslation extends Model
{
    protected $fillable = ['translatable_type', 'translatable_id', 'language_id', 'translation'];
    public function language() {
        return $this->belongsTo(Language::class);
    }
    public function translatable() {
        return $this->morphTo();
    }
}
