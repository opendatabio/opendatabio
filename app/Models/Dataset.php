<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lang;
use App\Models\User;
use App\Models\Taxon;
use DB;
use CodeInc\StripAccents\StripAccents;
use Illuminate\Support\Arr;

use Activity;

use Spatie\Activitylog\Traits\LogsActivity;

class Dataset extends Model
{
    use HasAuthLevels, LogsActivity;

    // These are the same from Project, but are copied in case we decide to add new levels to either model
    const PRIVACY_AUTH = 0;
    const PRIVACY_PROJECT = 1;
    const PRIVACY_REGISTERED = 2;
    const PRIVACY_PUBLIC = 3;
    const PRIVACY_LEVELS = [self::PRIVACY_AUTH, self::PRIVACY_PROJECT, self::PRIVACY_REGISTERED, self::PRIVACY_PUBLIC];

    protected $fillable = ['name', 'description', 'privacy', 'policy','metadata','title','license','project_id'];

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

    public function getFullnameAttribute()
    {
      return $this->name;
    }

    //related models
    public function measurements()
    {
        return $this->hasMany(Measurement::class);
    }
    public function individuals()
    {
        return $this->hasMany(Individual::class);
    }
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }
    public function measuredVouchers( )
    {
        return $this->hasManyThrough(
                      'App\Models\Voucher',
                      'App\Models\Measurement',
                      'dataset_id', // Foreign key on Measurement table...
                      'id', // Foreign key on individual table...
                      'id', // Local key on Dataset table...
                      'measured_id' // Local key on Measurement table...
                      )->where('measurements.measured_type', 'App\Models\Voucher')->distinct();
    }
    public function measuredIndividuals( )
    {
        return $this->hasManyThrough(
                      'App\Models\Individual',
                      'App\Models\Measurement',
                      'dataset_id', // Foreign key on Measurement table...
                      'id', // Foreign key on individual table...
                      'id', // Local key on Dataset table...
                      'measured_id' // Local key on Measurement table...
                      )->where('measurements.measured_type', 'App\Models\Individual')->distinct();
    }

    public function individualLocations( )
    {
        return $this->hasManyThrough(
                      'App\Models\IndividualLocation',
                      'App\Models\Individual',
                      'dataset_id', // Foreign key on Measurement table...
                      'individual_id', // Foreign key on individual table...
                      'id', // Local key on Dataset table...
                      'id' // Local key on Measurement table...
                      );
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
        if ($this->privacy==self::PRIVACY_PROJECT) {
          return $this->project->admins->first()->email;
        }
        return $this->admins->first()->email;
    }

    public function measured_classes_counts()
    {
      return DB::table('measurements')->selectRaw('measured_type,count(*) as count')->where('dataset_id',$this->id)->groupBy('measured_type')->get();
    }

    /* the id of all vouchers DIRECTLY related to the dataset */
    public function all_voucher_ids()
    {
      $ids_v = $this->vouchers()->withoutGlobalScopes()->pluck("id")->toArray();
      $ids_i = $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%voucher%')->pluck("measured_id")->toArray();
      $ids_m = $this->media()->withoutGlobalScopes()->where('model_type','like','%voucher%')->pluck("model_id")->toArray();
      $final = array_merge($ids_v,$ids_i,$ids_m);
      return array_unique($final);
    }

    public function all_locations_ids()
    {
      $ids_i = $this->all_individuals_ids();
      $locs = [];
      if (count($ids_i)) {
        $locs = IndividualLocation::whereIn('individual_id',$ids_i)->cursor()->map(function($il) {
          return $il->all_locations_ids();
        })->toArray();
      }
      $locs_m = $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%location%')->pluck("measured_id")->toArray();
      $locs_me = $this->media()->withoutGlobalScopes()->where('model_type','like','%location%')->pluck("model_id")->toArray();
      $final = array_merge($locs,$locs_m,$locs_me);
      return array_unique($final);
    }

    /* the id of all individuls DIRECTLY related to the dataset */
    public function all_individuals_ids()
    {
      $ids_i = $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%individual%')->pluck("measured_id")->toArray();
      $ids_b = $this->individuals()->withoutGlobalScopes()->pluck("id")->toArray();
      $ids_m = $this->media()->withoutGlobalScopes()->where('model_type','like','%individual%')->pluck("model_id")->toArray();
      $ids_v = $this->vouchers()->withoutGlobalScopes()->cursor()->map(function($v) { return $v->individual_id;})->toArray();
      $ids_mv = $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%voucher%')->cursor()->map(function($v) { return $v->measured->individual_id;})->toArray();
      $ids_mev = $this->media()->withoutGlobalScopes()->where('model_type','like','%individual%')->cursor()->map(function($v) { return $v->voucher->individual_id;})->toArray();
      $final = array_merge($ids_i,$ids_b,$ids_m,$ids_v,$ids_mv,$ids_mev);
      return array_unique($final);
    }
    public function all_media_ids()
    {
      $ids_a = $this->individuals()->withoutGlobalScopes()->cursor()->map(function($v) { return $v->media()->pluck('id')->toArray();})->toArray();
      $ids_b = $this->media()->withoutGlobalScopes()->pluck("id")->toArray();
      $ids_c = $this->vouchers()->withoutGlobalScopes()->cursor()->map(function($v) { return $v->media()->pluck('id')->toArray();})->toArray();
      $ids_d = Location::whereIn('id',$this->all_locations_ids())->cursor()->map(function($v) { return $v->media()->pluck('id')->toArray();})->toArray();
      $ids_e = Taxon::whereIn('id',$this->all_taxons_ids())->cursor()->map(function($v) { return $v->media()->pluck('id')->toArray();})->toArray();
      $final = Arr::flatten(array_merge($ids_a,$ids_b,$ids_c,$ids_d,$ids_e));
      return array_unique($final);
    }

    public function identification_summary()
    {
      $ids = $this->all_individuals_ids();
      if (count($ids)) {
        return DB::table('identifications')->join('taxons','taxon_id','=','taxons.id')->selectRaw("taxons.level, SUM(IF(taxons.author_id IS NULL,1,0)) as published,  SUM(IF(taxons.author_id IS NULL,0,1)) as unpublished, COUNT(taxons.id) as total")->where('object_type','like','%individual%')->whereIn('object_id',$ids)->groupBy('taxons.level')->get();
      } else {
        return null;
      }
  }

    public function traits_summary()
    {
      if ($this->measurements()->withoutGlobalScopes()->count()==0) {
        return [];
      }
      $trait_summary = DB::select("SELECT measurements.trait_id,traits.export_name,
        SUM(CASE measured_type WHEN 'App\\\Models\\\Individual' THEN 1 ELSE 0 END) AS individuals,
        SUM(CASE measured_type WHEN 'App\\\Models\\\Voucher' THEN 1 ELSE 0 END) AS vouchers,
        SUM(CASE measured_type WHEN 'App\\\Models\\\Taxon' THEN 1 ELSE 0 END) AS taxons,
        SUM(CASE measured_type WHEN 'App\\\Models\\\Location' THEN 1 ELSE 0 END) AS locations,
        count(*)  as total
        FROM measurements LEFT JOIN traits ON traits.id=measurements.trait_id WHERE measurements.dataset_id= ? GROUP BY measurements.trait_id,traits.export_name  ORDER BY traits.export_name",[$this->id]);
      return $trait_summary;
    }



   public function all_taxons_ids()
   {
     $taxons_direct = [];
     $taxons_indirect = [];
     $ids = $this->all_individuals_ids();
     if (count($ids)) {
       $taxons_indirect = Identification::whereIn("object_id",$ids)->where('object_type','like','%individual%')->pluck("taxon_id")->toArray();
     }
     $taxons_direct =  $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%Taxon')->pluck('measured_id')->toArray();
     $taxons = array_unique(array_merge($taxons_indirect,$taxons_direct));
     return $taxons;
  }

   public function taxonomic_summary()
   {
     $taxons_ids = $this->all_taxons_ids();
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
       return $this->morphMany("App\Models\Summary", 'object');
   }

   public function summary_scopes()
   {
       return $this->morphMany("App\Models\Summary", 'scope');
   }

   public function getCount($scope="all",$scope_id=null,$target='individuals')
   {
     if (Measurement::count()==0) {
       return 0;
     }
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
     $taxons_direct =  $this->measurements()->withoutGlobalScopes()->where('measured_type','=','App\Models\Taxon')->pluck('measured_id')->toArray();
     $taxons = array_unique(array_merge($taxons_individuals,$taxons_vouchers,$taxons_direct));
     $count = Taxon::whereIn('id',$taxons)->where('level',">=",$count_level);
     return  $count;
   }

   // TODO: weird count for dataset taxa
   public function taxonsCount()
   {
     //$count_level = Taxon::getRank('species');
     //return $this->summary_scopes()->whereHasMorph('object',['App\Models\Taxon'],function($object) use($count_level) { $object->where('level','>=',$count_level);})->where('object_type','App\Models\Taxon')->selectRaw("DISTINCT object_id")->cursor()->count();
     //$ids = $this->taxons_ids();
     //$count_level = Taxon::getRank('species');
     //return Taxon::whereIn('id',$ids)->where('level',$count_level)->count();
     return count($this->all_taxons_ids());
   }


   public function locationsCount()
   {
     $individuals = $this->individuals()->withoutGlobalScopes()->cursor()->map(function($individual) {
       return $individual->location_first->pluck('location_id')->toArray()[0];
     })->toArray();
     return count(array_unique(array_merge($individuals)));
   }

   /*
   public function projectsCount()
   {
     $individuals = $this->individuals()->withoutGlobalScopes()->cursor()->pluck('project_id')->toArray();
     $vouchers = $this->vouchers()->withoutGlobalScopes()->cursor()->pluck('project_id')->toArray();
     return count(array_unique(array_merge($individuals,$vouchers)));
   }
   */
   /* PROJECT */
   public function project()
   {
       return $this->belongsTo(Dataset::class);
   }
   public function getProjectNameAttribute()
   {
       if ($this->project) {
           return $this->project->name;
       }
       return 'Unknown dataset';
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

   public function generateCitation($short=true,$for_dt=false,$html=true)
   {
     if ($short) {
       $author = $this->short_authors;
     } else {
       $author = $this->all_authors;
     }
     $when = today()->format("Y-m-d");
     $year = isset($this->last_edition_date) ? $this->last_edition_date->format('Y') : 'no data yet';
     $version = isset($this->last_edition_date) ? $this->last_edition_date->format('Y-m-d') : 'no data yet';
     $license = (null != $this->license) ? $this->license : 'Not defined, some restrictions may apply';
     if (preg_match("/CC0/i",$license)) {
       $license = "Public domain - CC0";
     }
     $title = isset($this->title) ? $this->title : $this->name;
     if ($for_dt) {
       if ($html) {
         $title = "<a href='".url('datasets/'.$this->id)."'>".htmlspecialchars($title).'</a>';
       }
     }
     if (null != $author) {
       if ($html) {
         $citation = $author." (".$year.").  <strong>".$title."</strong>. Version: ".$version.".";
       } else {
         $citation = $author." (".$year."). ".$title.". Version: ".$version.".";
       }
     } else {
       if ($html) {
         $citation = "<strong>".$title."</strong>. Version: ".$version.".";
       } else {
         $citation = $title." Version: ".$version.".";
       }
     }
     /* ONLY ADDED WHEN NOT RESTRICTED */
     if ($this->privacy > self::PRIVACY_PROJECT) {
       $citation .= " License: ".$license;
     } else {
       $citation .= " License: access is private, data has restrictions";
     }
     if (!$for_dt) {
       $url =  url('datasets/'.$this->id);
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
     $license = (null != $this->license and $this->privacy <= self::PRIVACY_PROJECT) ? $this->license : 'Not public, some restrictions may apply.';

     if (preg_match("/CC0/i",$license)) {
       $license = "Public domain - CC0";
     }

     $version = "Version: ".$this->last_edition_date->format("Y-m-d")." ";
     $bib =  [
        'title' => isset($this->title) ? $this->title : $this->name,
        'year' => $this->last_edition_date->format("Y"),
        'author' => $this->all_authors,
        'howpublished' => "url\{".url('dataset/'.$this->id)."}",
        'version' => $version,
        'license' => $license,
        'note' => $version.$license." Accessed: ".today()->format("Y-m-d"),
        'url' => "{".url('dataset/'.$this->id)."}",
     ];
     return "@misc{".$bibkey.",\n".json_encode($bib,JSON_PRETTY_PRINT);
   }

   /* FUNCTIONS FOR GETTING SUMMARY CONTENT FOR THE DATASET */

   /* the percentage of individuals that belong to a plot in the dataset */
   public function plot_included()
   {
      /* individuals are from plots or transects */
      $adm_levels = implode(",",[Location::LEVEL_PLOT,Location::LEVEL_TRANSECT]);
      $ninplots  = DB::select("SELECT COUNT(*) as inplots FROM individual_location as idj LEFT JOIN individuals as idv ON idv.id=idj.individual_id LEFT JOIN locations as locs ON locs.id=idj.location_id LEFT JOIN locations as parentlocs ON parentlocs.id=locs.parent_id WHERE idv.dataset_id=".$this->id." AND (locs.adm_level IN($adm_levels) OR (parentlocs.adm_level IN($adm_levels) AND locs.adm_level=".Location::LEVEL_POINT."))")[0]->inplots;

      /* measurements are from individuals in plots */
      $query  = DB::select("SELECT COUNT(DISTINCT(idj.individual_id)) as inplots, COUNT(DISTINCT(meas.id)) as measured FROM individual_location as idj LEFT JOIN locations as locs ON locs.id=idj.location_id LEFT JOIN locations as parentlocs ON parentlocs.id=locs.parent_id LEFT JOIN measurements AS meas ON meas.measured_id=idj.individual_id WHERE meas.dataset_id=".$this->id." AND meas.measured_type LIKE '%individual%' AND (locs.adm_level IN($adm_levels) OR (parentlocs.adm_level IN($adm_levels) AND locs.adm_level=".Location::LEVEL_POINT."))")[0];

      $plotids   =  DB::select("SELECT IF(locs.adm_level=".Location::LEVEL_POINT.",locs.parent_id,locs.id) as plotids FROM individual_location as idj LEFT JOIN individuals as idv ON idv.id=idj.individual_id LEFT JOIN locations as locs ON locs.id=idj.location_id LEFT JOIN locations as parentlocs ON parentlocs.id=locs.parent_id WHERE idv.dataset_id=".$this->id." AND (locs.adm_level IN($adm_levels) OR (parentlocs.adm_level IN($adm_levels) AND locs.adm_level=".Location::LEVEL_POINT."))");
      $plotids_m  = DB::select("SELECT IF(locs.adm_level=".Location::LEVEL_POINT.",locs.parent_id,locs.id) as plotids FROM individual_location as idj LEFT JOIN locations as locs ON locs.id=idj.location_id LEFT JOIN locations as parentlocs ON parentlocs.id=locs.parent_id LEFT JOIN measurements AS meas ON meas.measured_id=idj.individual_id WHERE meas.dataset_id=".$this->id." AND meas.measured_type LIKE '%individual%' AND (locs.adm_level IN($adm_levels) OR (parentlocs.adm_level IN($adm_levels) AND locs.adm_level=".Location::LEVEL_POINT."))");
      $plotids = array_unique(array_merge($plotids,$plotids_m));

      $result = [
        'Number of Plots' => count($plotids),
        'Individuals in Plots' => $ninplots,
        'Individuals in Plots measured' => $query->inplots,
        'Measurements from Plot individuals' => $query->measured,
      ];
      $result = array_filter($result,function($v) { return $v>0;});
      return $result;
   }
   public function data_included()
   {
     $type = [
       "MeasurementsOrFacts" => [
           'Total' => $this->measurements()->withoutGlobalScopes()->count(),
           'Individuals' => $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%individual%')->count(),
           'Vouchers' => $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%voucher%')->count(),
           'Taxons' => $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%taxon%')->count(),
           'Locations' => $this->measurements()->withoutGlobalScopes()->where('measured_type','like','%location%')->count(),
       ],
       "MediaFiles" => [
           'Total' => $this->media()->withoutGlobalScopes()->count(),
           'Individuals' => $this->media()->withoutGlobalScopes()->where('model_type','like','%individual%')->count(),
           'Vouchers' => $this->media()->withoutGlobalScopes()->where('model_type','like','%voucher%')->count(),
           'Taxons' => $this->media()->withoutGlobalScopes()->where('model_type','like','%taxon%')->count(),
           'Locations' => $this->media()->withoutGlobalScopes()->where('model_type','like','%location%')->count(),
        ],
     "Occurrences" => [
          'Total' => $this->individuals()->withoutGlobalScopes()->count(),
          'Individuals' => $this->individuals()->withoutGlobalScopes()->count(),
          'Vouchers' => null,
          'Taxons' => null,
          'Locations' => null,
       ],
     "PreservedSpecimens" => [
         'Total' => $this->vouchers()->withoutGlobalScopes()->count(),
         'Individuals' => count(array_unique($this->vouchers()->withoutGlobalScopes()->pluck("individual_id")->toArray())),
         'Vouchers' => $this->vouchers()->withoutGlobalScopes()->count(),
         'Taxons' => null,
         'Locations' => null,
      ],
    ];
    $type = array_filter($type,function($v) { return $v["Total"]>0;});
    return $type;
   }


    public function getDataTypeAttribute()
    {
      $type = [];
      $type["MeasurementsOrFacts"] = $this->measurements()->withoutGlobalScopes()->count();
      $type["MediaFiles"] = $this->media()->withoutGlobalScopes()->count();
      $type["Organisms"] = $this->individuals()->withoutGlobalScopes()->count();
      $type["Occurrences"] = $this->individualLocations()->withoutGlobalScopes()->count();
      $type["PreservedSpecimens"] = $this->vouchers()->withoutGlobalScopes()->count();
      $type = array_filter($type,function($v) { return $v>0;});
      return $type;
    }

    public function getDataTypeRawLink()
    {
      $types = $this->data_type;
      if (count($types)==0) {
        return null;
      }
      $urls = [
        'MeasurementsOrFacts' => 'measurements/'.$this->id.'/dataset',
        'MediaFiles' => 'media/'.$this->id.'/datasets',
        'Organisms' => 'individuals/'.$this->id.'/dataset',
        'Occurrences' => 'individuals/'.$this->id.'/location-dataset',
        'PreservedSpecimens' => 'vouchers/'.$this->id.'/dataset',
      ];
      $tips = [
        'MeasurementsOrFacts' => Lang::get('messages.tooltip_view_measurements'),
        'MediaFiles' => Lang::get('messages.tooltip_view_media'),
        'Organisms' => Lang::get('messages.tooltip_view_individuals'),
        'Occurrences' => Lang::get('messages.tooltip_view_individual_locations'),
        'PreservedSpecimens' => Lang::get('messages.tooltip_view_vouchers')
      ];
      $string = [];
      foreach($types as $key => $val) {
          $string[] = '<a href="'.url($urls[$key]).'" data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.$tips[$key].'" >'.$key.':&nbsp;'.$val.'</a>';
      }
      $string = implode("<br>",$string);
      return $string;
    }

    //get date of last edit in this dataset
    public function getLastEditionDateAttribute()
    {
      $lastdate = [];
      if ($this->measurements()->withoutGlobalScopes()->count()) {
        $lastdate[] = $this->measurements()->withoutGlobalScopes()->select('measurements.updated_at')->orderBy('updated_at','desc')->first()->updated_at;
      }
      if ($this->media()->withoutGlobalScopes()->count()) {
          $lastdate[] = $this->media()->withoutGlobalScopes()->select('media.updated_at')->orderBy('updated_at','desc')->first()->updated_at;
      }
      if ($this->individuals()->withoutGlobalScopes()->count()) {
          $lastdate[] = $this->individuals()->withoutGlobalScopes()->select('individuals.updated_at')->orderBy('updated_at','desc')->first()->updated_at;
      }
      if ($this->vouchers()->withoutGlobalScopes()->count()) {
          $lastdate[] = $this->vouchers()->withoutGlobalScopes()->select('vouchers.updated_at')->orderBy('updated_at','desc')->first()->updated_at;
      }
      /*
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
        $identification_last_editions = Identification::select('identifications.updated_at')->whereIn('object_id',$id1)->where('object_type','App\Models\Individual')->orderBy('updated_at','desc')->first()->updated_at;
        if ($identification_last_editions>$lastdate) {
            $lastdate = $identification_last_editions;
        }
      }
      */
      if (count($lastdate)==0) {
        return null;
      }
      asort($lastdate);
      return $lastdate[count($lastdate)-1];
   }

   public function getDownloadsAttribute()
   {
     return $this->morphMany("Activity", 'subject')->where('description','like','%download%')->count();
   }

   public static function getTableName()
   {
       return (new self())->getTable();
   }


   /* dwc termsn */
   public function getAccessRightsAttribute()
   {
     if (in_array($this->privacy,[self::PRIVACY_AUTH,self::PRIVACY_PROJECT])) {
       $rights =  'Restricted access.';
     } else {
       $rights = "Open access.";
       if ($this->privacey==self::PRIVACY_REGISTERED) {
         $rights .=  ' Require user registration.';
       }
    }
    if ($this->policy) {
      $rights .= " Has the following policy: ".$this->policy;
    }
    $url = url('datasets/'.$this->id);
    return $rights." URL: ".$url;
   }

    public function getBibliographicCitationAttribute()
    {
      return $this->generateCitation($short=false,$for_dt=false,$html=false);
    }

    public function getDwcLicenseAttribute()
    {
      if ($this->policy) {
        return $this->license." | Policy:".$this->policy;
      }
      return $this->license;
    }

    public function getPeopleAttribute()
    {
      if ($this->privacy==self::PRIVACY_PROJECT) {
        return $this->project->people;
      }

      $admins = $this->admins->map(function($u) {
        $person = isset($u->person_id) ? $u->person->full_name : null;
        return [$u->email,$person];
      });
      $collabs = $this->collabs->map(function($u) {
        $person = isset($u->person_id) ? $u->person->full_name : null;
        return [$u->email,$person];
      });
      $viewers = $this->viewers->map(function($u) {
        $person = isset($u->person_id) ? $u->person->full_name : null;
        return [$u->email,$person];
      });
      return [
        'admins' => $admins->toArray(),
        'collabs' => $collabs->toArray(),
        'viewers' => $viewers->toArray(),
      ];
    }



}
