<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\ODBTrait;

class Form extends Model
{
    protected $fillable = ['name', 'measured_type', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function traits() {
        return $this->belongsToMany(ODBTrait::class, 'form_traits', 'form_id', 'trait_id')->withPivot('order');
    }
    public function getTrait($i) {
        foreach ($this->traits as $odbtrait) {
            if ($odbtrait->pivot->order == $i) {
                return $odbtrait;
            }
        }
    }
}
