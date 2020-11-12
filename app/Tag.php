<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use Translatable;

    public function rawLink()
    {
      return "<a href='".url('tags/'.$this->id)."'>".htmlspecialchars($this->name).'</a>';
      // code...
    }

    public function datasets()
    {
        return $this->belongsToMany(Dataset::class);
    }

    public function pictures()
    {
        return $this->belongsToMany(Picture::class);
    }
    public function projects()
    {
      return $this->belongsToMany(Project::class);
    }
}
