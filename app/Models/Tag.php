<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Dataset;
use App\Models\Media;
use App\Models\Project;

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

    public function media()
    {
        return $this->belongsToMany(Media::class);
    }
    public function projects()
    {
      return $this->belongsToMany(Project::class);
    }
}
