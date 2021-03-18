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

    protected $fillable = ['object_id', 'object_type','license','notes','original_metadata'];

    public function object()
    {
        return $this->morphTo();
    }


    public function collectors()
    {
      return $this->morphMany(Collector::class, 'object')->with('person');
    }


    public function getTaxonNameAttribute()
    {
      if ($this->object_type == 'App\Individual' or $this->object_type == 'App\Voucher' ) {
        return $this->object()->withoutGlobalScopes()->first()->taxon_name;
      }
      if ($this->object_type == 'App\Taxon' ) {
        return $this->object()->first()->fullname;
      }
      return null;
    }

    public function getAllCollectorsAttribute()
    {
        $persons = $this->collectors->map(function($person) { return $person->person->abbreviation;})->toArray();
        $persons = implode(' & ',$persons);
        return $persons;
    }

    public function getCitationAttribute()
    {
      return $this->generateCitation($include_assessed=TRUE);
    }

    public function generateCitation($include_assessed=FALSE)
    {
      $citation = "";
      if ("" != $this->title) {
          $citation = $this->title.". ";
      }
      $citation .= "(".$this->year.") ".$this->all_collectors;
      $citation .= " License: ".$this->license;
      if ($include_assessed) {
        $citation .= " From: ".url("pictures/".$this->id).", accessed ".today()->format('Y-m-d').".";
      }
      return $citation;
    }

    public function getTitleAttribute()
    {
      $description = (isset($mm->description) and  !preg_match("/Missing/i",$mm->description)) ? $mm->description.". " : "";
      if ($this->object_type == 'App\Location' ) {
        return  $description.$this->object()->first()->name." ".$this->object()->withGeom()->first()->coordinates_simple.". ";
      }
      $taxon = isset($this->taxon_name) ? $this->taxon_name : "";
      return  $description.$taxon;
    }


    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function getTagLinksAttribute()
    {
        if (empty($this->tags)) {
            return '';
        }
        $ret = '';
        $i=0;
        foreach ($this->tags as $tag) {
            if ($i>0) {
              $ret .= " | ";
            }
            $ret .= "<a href='".url('tags/'.$tag->id)."'>".htmlspecialchars($tag->name).'</a>';
            $i++;
        }

        return $ret;
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
    public function saveImage($img)
    {
        if (!$this->object_type or !$this->id) {
            throw new Exception('saveImage requires that the id and object_type are set first!');
        }
        $path = public_path('upload_pictures/'.$this->filename());
        //$img = Image::make($image);
        $img->save($path);

        // save thumbnail
        $path = public_path('upload_pictures/'.$this->filename(true));
        if ($img->width() > $img->height()) {
            $img->widen(150)->save($path);
        } else {
            $img->heighten(150)->save($path);
        }
    }

    public function getYearAttribute()
    {
      if (null != $this->metadata) {
          $metadata = json_decode($this->metadata);
          $dto = isset($metadata->DateTimeOriginal) ? strtotime($metadata->DateTimeOriginal) : null;
          $dtd = isset($metadata->DateTimeDigitized) ? strtotime($metadata->DateTimeDigitized) : null;
          if (!$dto and !$dtd) {
            return null;
          }
          if ($dtd and $dtd > $dto) {
             return (int) date("Y",$dtd);
          }
          if ($dto) {
             return (int) date("Y",$dto);
          }
      }
      return null;
    }

}
