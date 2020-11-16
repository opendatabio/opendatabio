<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Lang;
use App\User;
use App\Taxon;
use DB;

class Dataset extends Model
{
    use HasAuthLevels;

    // These are the same from Project, but are copied in case we decide to add new levels to either model
    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [self::PRIVACY_AUTH, self::PRIVACY_REGISTERED, self::PRIVACY_PUBLIC];

    protected $fillable = ['name', 'description', 'privacy', 'bibreference_id','policy','metadata'];

    public function rawLink()
    {
        return "<a href='".url('datasets/'.$this->id)."' data-toggle='tooltip' rel='tooltip' data-placement='right' title='Dataset details'>".htmlspecialchars($this->name).'</a>';
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class);
    }


    public function vouchers( )
    {
        return $this->hasManyThrough(
                      'App\Voucher',
                      'App\Measurement',
                      'dataset_id', // Foreign key on Measurement table...
                      'id', // Foreign key on Plant table...
                      'id', // Local key on Dataset table...
                      'measured_id' // Local key on Measurement table...
                      )->where('measurements.measured_type', 'App\Voucher')->distinct();
    }

    public function plants( )
    {
        return $this->hasManyThrough(
                      'App\Plant',
                      'App\Measurement',
                      'dataset_id', // Foreign key on Measurement table...
                      'id', // Foreign key on Plant table...
                      'id', // Local key on Dataset table...
                      'measured_id' // Local key on Measurement table...
                      )->where('measurements.measured_type', 'App\Plant')->distinct();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
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

    public function getTaggedWidthAttribute()
    {
        if (empty($this->tags)) {
            return '';
        }
        $ret = '';
        foreach ($this->tags as $tag) {
            $ret .= $tag->name;
        }

        return $ret;
    }

    public function reference()
    {
        return $this->belongsTo(BibReference::class, 'bibreference_id');
    }

    public function references()
    {
        //return $this->belongsToMany(BibReference::class,'dataset_bibreference')->withTimestamps();
        return $this->belongsToMany(BibReference::class,'dataset_bibreference')->withPivot(['mandatory'])->withTimestamps();
    }


    // for use in the trait edit dropdown
    public function getPrivacyLevelAttribute()
    {
        return Lang::get('levels.privacy.'.$this->privacy);
    }

    public function getContactEmailAttribute()
    {
        return $this->users()->wherePivot('access_level', '=', User::ADMIN)->first()->email;
    }

/* Summarize what the dataset is about */
public function plants_ids()
{
  return Measurement::withoutGlobalScopes()->where('dataset_id',$this->id)->where('measured_type','App\Plant')->distinct('measured_id');
}

public function vouchers_ids()
{
  return Measurement::withoutGlobalScopes()->where('dataset_id',$this->id)->where('measured_type','App\Voucher')->distinct('measured_id');
}

public function measured_classes_counts()
{
  return DB::table('measurements')->selectRaw('measured_type,count(*) as count')->where('dataset_id',$this->id)->groupBy('measured_type')->get();
}

public function identification_summary()
{
  return DB::table('identifications')->join('measurements','measured_id','=','object_id')->join('taxons','taxon_id','=','taxons.id')->selectRaw(
"taxons.level, SUM(IF(taxons.author_id IS NULL,1,0)) as published,  SUM(IF(taxons.author_id IS NULL,0,1)) as unpublished, COUNT(taxons.id) as total")->whereRaw('measured_type=object_type')->where('dataset_id',$this->id)->groupBy('level')->get();
}

public function traits_summary()
{

  $trait_summary = DB::select("SELECT measurements.trait_id,traits.export_name,
    SUM(CASE measured_type WHEN 'App\\\Plant' THEN 1 ELSE 0 END) AS plants,
    SUM(CASE measured_type WHEN 'App\\\Voucher' THEN 1 ELSE 0 END) AS vouchers,
    SUM(CASE measured_type WHEN 'App\\\Taxon' THEN 1 ELSE 0 END) AS taxons,
    SUM(CASE measured_type WHEN 'App\\\Location' THEN 1 ELSE 0 END) AS locations,
    count(*)  as total
    FROM measurements LEFT JOIN traits ON traits.id=measurements.trait_id WHERE measurements.dataset_id= ? GROUP BY measurements.trait_id,traits.export_name  ORDER BY traits.export_name",[$this->id]);
    return $trait_summary;
}

  public function taxons_count()
  {
    $count_measured_identifications = DB::table('identifications')->join('measurements','measured_id','=','object_id')->distinct('taxon_id')->whereRaw('measured_type=object_type')->where('dataset_id',$this->id)->count();
    $count_measured_taxa =  $this->measurements()->where('measured_type','App\Taxon')->distinct('measured_id')->count();
    return ($count_measured_identifications+$count_measured_taxa);
 }

 public function taxons_ids()
 {
   $taxons_ids =  [];
   $taxons_ids2 = [];
   $query = DB::select("(SELECT DISTINCT taxon_id  FROM identifications  JOIN measurements ON measurements.measured_id=identifications.object_id WHERE identifications.object_type=measurements.measured_type AND measurements.dataset_id=".$this->id.")");
   if (count($query)) {
    $taxons_ids  = array_map(function($value) { return $value->taxon_id;},$query);
   }
   $query2 = DB::select('(SELECT DISTINCT measured_id  FROM measurements  WHERE measured_type="App\\\Taxon" AND measurements.dataset_id='.$this->id.')');
   if (count($query2)) {
    $taxons_ids2  = array_map(function($value) { return $value->measured_id;},$query2);
   }
   return array_unique(array_merge($taxons_ids,$taxons_ids2));

}
 public function taxonomic_summary()
 {
   $taxons_ids = $this->taxons_ids();

   if (count($taxons_ids)) {
     $ids = implode(",",$taxons_ids);
     $query = DB::select('SELECT COUNT(DISTINCT tb.fam) as families, COUNT(DISTINCT tb.genus) as genera, COUNT(DISTINCT tb.species) as species FROM (SELECT odb_txparent(taxons.lft,120) as fam,odb_txparent(taxons.lft,180) as genus, odb_txparent(taxons.lft,210) as species FROM taxons WHERE taxons.id IN('.$ids.')) as tb');
     $query  = array_map(function($value) { return (array)$value;},$query);

     return $query[0];
   } else {
     return null;
   }
 }


}
