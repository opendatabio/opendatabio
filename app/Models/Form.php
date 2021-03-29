<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $fillable = ['name', 'measured_type', 'user_id', 'notes'];

    public function rawLink()
    {
        return "<a href='".url('forms/'.$this->id)."'>".htmlspecialchars($this->name).'</a>';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function traits()
    {
        return $this->belongsToMany(ODBTrait::class, 'form_traits', 'form_id', 'trait_id')->withPivot('order');
    }

    public function getTrait($i)
    {
        foreach ($this->traits as $odbtrait) {
            if ($odbtrait->pivot->order == $i) {
                return $odbtrait;
            }
        }
    }
}
