<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Facades\Image;

class Picture extends Model
{
    use Translatable;

    protected $fillable = ['object_id', 'object_type'];

    public function object()
    {
        return $this->morphTo();
    }

    public function collectors()
    {
        return $this->morphMany(Collector::class, 'object');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    // Gets the image path to display it
    public function url($thumb = false)
    {
        return url('upload_pictures/'.$this->filename($thumb));
    }

    public function typeShort()
    {
        return strtolower(trim(substr($this->object_type, strrpos($this->object_type, '\\') - strlen($this->object_type) + 1)));
    }

    public function filename($thumb = false)
    {
        return ($thumb ? 't_' : '').$this->typeShort().'_'.$this->id.'.jpg';
    }

    // Saves the image to the filesystem
    public function saveImage($image)
    {
        if (!$this->object_type or !$this->id) {
            throw new Exception('saveImage requires that the id and object_type are set first!');
        }
        $path = public_path('upload_pictures/'.$this->filename());
        $img = Image::make($image);
        $img->save($path);
        // save thumbnail
        $path = public_path('upload_pictures/'.$this->filename(true));
        if ($img->width() > $img->height()) {
            $img->widen(150)->save($path);
        } else {
            $img->heighten(150)->save($path);
        }
    }
}
