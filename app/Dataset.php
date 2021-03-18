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
use CodeInc\StripAccents\StripAccents;
use Spatie\Activitylog\Traits\LogsActivity;
use Activity;

class Dataset extends Model
{
    use HasAuthLevels, LogsActivity;

    // These are the same from Project, but are copied in case we decide to add new levels to either model
    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [self::PRIVACY_AUTH, self::PRIVACY_REGISTERED, self::PRIVACY_PUBLIC];

    protected $fillable = ['name', 'description', 'privacy', 'policy','metadata','title','license'];

    //activity log trait to audit changes in record
    protected static $logName = 'dataset';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at'];
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;


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
                      'id', // Foreign key on individual table...
                      'id', // Local key on Dataset table...
                      'measured_id' // Local key on Measurement table...
                      )->where('measurements.measured_type', 'App\Voucher')->distinct();
    }

    public function individuals( )
    {
        return $this->hasManyThrough(
                      'App\Individual',
                      'App\Measurement',
                      'dataset_id', // Foreign key on Measurement table...
                      'id', // Foreign key on individual table...
                      'id', // Local key on Dataset table...
                      'measured_id' // Local key on Measurement table...
                      )->where('measurements.measured_type', 'App\Individual')->distinct();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }


    /* authorship */
    public function authors()
    {
      return $this->morphMany(Collector::class, 'object')->with('person');
    }

    public function author_first()
    {
        return $this->authors()->where('main',1);
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



    public function references()
    {
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



    public function measured_classes_counts()
    {
      return DB::table('measurements')->selectRaw('measured_type,count(*) as count')->where('dataset_id',$this->id)->groupBy('measured_type')->get();
    }


    // TODO: NEEDS UPDATE - ignores voucher identification
    public function identification_summary()
    {
      return DB::table('identifications')->join('measurements','measured_id','=','object_id')->join('taxons','taxon_id','=','taxons.id')->selectRaw(
    "taxons.level, SUM(IF(taxons.author_id IS NULL,1,0)) as published,  SUM(IF(taxons.author_id IS NULL,0,1)) as unpublished, COUNT(taxons.id) as total")->whereRaw('measured_type=object_type')->where('dataset_id',$this->id)->groupBy('level')->get();
  }

    public function traits_summary()
    {

      $trait_summary = DB::select("SELECT measurements.trait_id,traits.export_name,
        SUM(CASE measured_type WHEN 'App\\\Individual' THEN 1 ELSE 0 END) AS individuals,
        SUM(CASE measured_type WHEN 'App\\\Voucher' THEN 1 ELSE 0 END) AS vouchers,
        SUM(CASE measured_type WHEN 'App\\\Taxon' THEN 1 ELSE 0 END) AS taxons,
        SUM(CASE measured_type WHEN 'App\\\Location' THEN 1 ELSE 0 END) AS locations,
        count(*)  as total
        FROM measurements LEFT JOIN traits ON traits.id=measurements.trait_id WHERE measurements.dataset_id= ? GROUP BY measurements.trait_id,traits.export_name  ORDER BY traits.export_name",[$this->id]);
        return $trait_summary;
    }



   public function taxons_ids()
   {
     $taxons_individuals  = $this->individuals()->withoutGlobalScopes()->cursor()->map(function($individual) {
       return $individual->identification->taxon_id;
     })->toArray();
     $taxons_vouchers  = $this->vouchers()->withoutGlobalScopes()->cursor()->map(function($voucher) {
       return $voucher->identification->taxon_id;
     })->toArray();
     $taxons_direct =  $this->measurements()->withoutGlobalScopes()->where('measured_type','=','App\Taxon')->pluck('measured_id')->toArray();
     $taxons = array_unique(array_merge($taxons_individuals,$taxons_vouchers,$taxons_direct));

     return $taxons;
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

   /* functions to interact with COUNT model*/
   public function summary_counts()
   {
       return $this->morphMany("App\Summary", 'object');
   }

   public function summary_scopes()
   {
       return $this->morphMany("App\Summary", 'scope');
   }

   public function getCount($scope="all",$scope_id=null,$target='individuals')
   {
     $query = $this->summary_counts()->where('scope_type',"=",$scope)->where('target',"=",$target);
     if (null !== $scope_id) {
       $query = $query->where('scope_id',"=",$scope_id);
     } else {
       $query = $query->whereNull('scope_id');
     }
     if ($query->count()) {
       return $query->first()->value;
     }
     return 0;
   }

   /* functions to generate counts */
   public function individualsCount()
   {
      return $this->individuals()->withoutGlobalScopes()->distinct('individuals.id')->count();
   }

   public function vouchersCount()
   {
       return $this->vouchers()->withoutGlobalScopes()->distinct('vouchers.id')->count();
   }

   public function measurementsCount()
   {
     return $this->measurements()->withoutGlobalScopes()->count();
   }

   public function taxonsCountOld()
   {
     $count_level = Taxon::getRank('species');
     $taxons_individuals  = $this->individuals()->withoutGlobalScopes()->cursor()->map(function($individual) {
       return $individual->identification->taxon_id;
     });
     $taxons_vouchers  = $this->vouchers()->withoutGlobalScopes()->cursor()->map(function($voucher) {
       return $voucher->identification->taxon_id;
     });
     $taxons_direct =  $this->measurements()->withoutGlobalScopes()->where('measured_type','=','App\Taxon')->pluck('measured_id')->toArray();
     $taxons = array_unique(array_merge($taxons_individuals,$taxons_vouchers,$taxons_direct));
     $count = Taxon::whereIn('id',$taxons)->where('level',">=",$count_level);
     return  $count;
   }

   // TODO: weird count for dataset taxa
   public function taxonsCount()
   {
     //$count_level = Taxon::getRank('species');
     //return $this->summary_scopes()->whereHasMorph('object',['App\Taxon'],function($object) use($count_level) { $object->where('level','>=',$count_level);})->where('object_type','App\Taxon')->selectRaw("DISTINCT object_id")->cursor()->count();
     //$ids = $this->taxons_ids();
     //$count_level = Taxon::getRank('species');
     //return Taxon::whereIn('id',$ids)->where('level',$count_level)->count();
     return count($this->taxons_ids());
   }


   public function locationsCount()
   {
     $individuals = $this->individuals()->withoutGlobalScopes()->cursor()->map(function($individual) {
       return $individual->location_first->pluck('location_id')->toArray()[0];
     })->toArray();
     return count(array_unique(array_merge($individuals)));
   }


   public function projectsCount()
   {
     $individuals = $this->individuals()->withoutGlobalScopes()->cursor()->pluck('project_id')->toArray();
     $vouchers = $this->vouchers()->withoutGlobalScopes()->cursor()->pluck('project_id')->toArray();
     return count(array_unique(array_merge($individuals,$vouchers)));
   }


   public function getAllAuthorsAttribute()
   {
     if ($this->authors->count()==0) {
       return null;
     }
     $persons = $this->authors->map(function($person) { return $person->person->abbreviation;})->toArray();
     $persons = implode(' and ',$persons);
     return $persons;
   }

   public function getShortAuthorsAttribute()
   {
     if ($this->authors->count()==0) {
       return null;
     }
     $persons = $this->authors->map(function($person) { return $person->person->abbreviation;})->toArray();
     if (count($persons)>2) {
       $persons = implode(' and ',$persons);
     } elseif (count($persons)>0) {
       $persons = $persons[0]." et al.";
     }
     return $persons;
   }

   public function getCitationAttribute()
   {
     return $this->generateCitation($short=false,$for_dt=false);
   }

   public function generateCitation($short=true,$for_dt=false)
   {
     if ($short) {
       $author = $this->short_authors;
     } else {
       $author = $this->all_authors;
     }
     $when = today()->format("Y-m-d");
     $year = isset($this->last_edition_date) ? $this->last_edition_date->format('Y') : 'no data yet';
     $version = isset($this->last_edition_date) ? $this->last_edition_date->format('Y-m-d') : 'no data yet';
     $license = (null != $this->license) ? $this->license : 'not defined, some restrictions may apply';
     if (preg_match("/CC0/i",$license)) {
       $license = "Public domain - CC0";
     }
     $title = isset($this->title) ? $this->title : $this->name;
     if ($for_dt) {
       $title = "<a href='".url('datasets/'.$this->id)."'>".htmlspecialchars($title).'</a>';
     }
     if (null != $author) {
       $citation = $author." (".$year.").  <strong>".$title."</strong>. Version: ".$version.". License: ".$this->license;
     } else {
       $citation = "<strong>".$title."</strong>. Version: ".$version.". License: ".$this->license;
     }
     if (!$for_dt) {
       $url =  url('dataset/'.$this->id);
       $citation .= '. From '.$url.', accessed '.$when.".";
     }
     return $citation;
   }

  public function getYearAttribute()
  {
    if ($this->last_edition_date) {
      return $this->last_edition_date->format('Y');
    }
    return null;
  }

  public function getVersionAttribute()
  {
    if ($this->last_edition_date) {
      return $this->last_edition_date->format("Y-m-d");
    }
    return null;
  }

   public function getBibtexAttribute()
   {
     if ($this->measurements()->withoutGlobalScopes()->count() ==0 ) {
       return null;
     }
     $url =  $this->name;
     $bibkey = preg_replace('[,| |\\.|-|_]','',StripAccents::strip( (string) $this->name ))."_".$this->last_edition_date->format("Y");
     $license = (null != $this->license) ? "License: ".$this->license.". " : "";
     $version = "Version: ".$this->last_edition_date->format("Y-m-d")." ";
     $bib =  [
        'title' => isset($this->title) ? $this->title : $this->name,
        'year' => $this->last_edition_date->format("Y"),
        'author' => $this->all_authors,
        'howpublished' => "url\{".url('dataset/'.$this->id)."}",
        'version' => $version,
        'license' => (null != $this->license) ? $this->license : 'License not defined, use restrictions may apply.',
        'note' => $version.$license." Accessed: ".today()->format("Y-m-d"),
        'url' => "{".url('dataset/'.$this->id)."}",
     ];
     return "@misc{".$bibkey.",\n".json_encode($bib,JSON_PRETTY_PRINT);
   }


    //get date of last edit in this dataset
    public function getLastEditionDateAttribute()
    {
      if ($this->measurements()->withoutGlobalScopes()->count() ==0 ) {
        return null;
      }
      $lastdate = $this->measurements()->withoutGlobalScopes()->select('measurements.updated_at')->orderBy('updated_at','desc')->first()->updated_at;
      //should get date of last identification also?
      $id1 = [];
      if ($this->individuals->count()) {
        $id1 = $this->individuals()->withoutGlobalScopes()->pluck('id')->toArray();
      }
      if ($this->vouchers->count()) {
        $id2 = $this->vouchers()->withoutGlobalScopes()->pluck('individual_id')->toArray();
        $id1 = array_unique(array_merge($id1,$id2));
      }
      if (count($id1)>0) {
        $identification_last_editions = Identification::select('identifications.updated_at')->whereIn('object_id',$id1)->where('object_type','App\Individual')->orderBy('updated_at','desc')->first()->updated_at;
        if ($identification_last_editions>$lastdate) {
            $lastdate = $identification_last_editions;
        }
      }
      return $lastdate;
   }

   public function getDownloadsAttribute()
   {
     return $this->morphMany("Activity", 'subject')->where('description','like','%downloads%')->count();
   }


   /*this will check whether a zipped filed with free access dataset exists */
   /*saving would allow unlogged users to download */
   public function hasPublicfile()
    {
      /* a file with this name will saved if the dataset is of public_access */
      $filename = 'dataset-'.$this->id.'_'.$this->last_edition_date->format('Y-m-d').'_.zip';
      $path = 'downloads_temp/'.$filename;
      if (file_exists(public_path($path))) {
          return [
            'file' => $filename,
            'version' => $this->last_edition_date->format('Y-m-d'),
            'last' => true
          ];
      }
      /* check if an older version exists */
      $files = scandir(public_path('downloads_temp'));
      $fn ='dataset-'.$this->id;
      $hasother = Arr::where($files, function ($value, $key) use($fn) {
          if ($start[0] == $fn) {
            return $value;
          }
      });
      if (count($hasother)>0) {
         $dt = explode("_",$hasother)[1];
         return [
           'file' => $hasother,
           'version' => $dt,
           'last' => false
         ];
      }
      return null;
    }


}
