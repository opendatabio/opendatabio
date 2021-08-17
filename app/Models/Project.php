<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Facades\Image;
use Lang;
use DB;
use App\Models\Taxon;
use Spatie\Activitylog\Traits\LogsActivity;
use CodeInc\StripAccents\StripAccents;

use Illuminate\Support\Arr;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Project extends Model implements HasMedia
{
    use HasAuthLevels, InteractsWithMedia, LogsActivity;

    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [self::PRIVACY_AUTH, self::PRIVACY_REGISTERED, self::PRIVACY_PUBLIC];

    const VIEWER = 0;
    const COLLABORATOR = 1;
    const ADMIN = 2;

    protected $fillable = ['name', 'description', 'details','title'];


    //activity log trait
    protected static $logName = 'project';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','details'];
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;



    public function rawLink()
    {
        return "<a href='".url('projects/'.$this->id)."'>".htmlspecialchars($this->fullname).'</a>';
    }

    /* relation to the datasets included in project */
    public function datasets()
    {
      return $this->hasMany(Dataset::class);
    }

    public function measurements()
    {
      return $this->hasManyThrough(
                    'App\Models\Measurement',
                    'App\Models\Dataset',
                    'project_id', // Foreign key on individual table...
                    'dataset_id', // Foreign key on identification table...
                    'id', // Local key on voucher table...
                    'id' // Local key on individual table...
                    );
    }

    public function themedia()
    {
      return $this->hasManyThrough(
                    'App\Models\Media',
                    'App\Models\Dataset',
                    'project_id', // Foreign key on dataset table...
                    'dataset_id', // Foreign key on media table...
                    'id', // Local key on project table...
                    'id' // Local key on dataset table...
                    );
    }
    /* ids for models retrieved from associated datasets */
    public function all_media_ids()
    {
      $ids = $this->datasets()->cursor()->map(function($d) {
        return $d->all_individuals_ids();
      })->toArray();
      $ids = Arr::flatten($ids);
      return array_unique($ids);
    }

    public function getMediaCountAttribute($value='')
    {
      return count($this->all_media_ids());
    }


    /* ids for models retrieved from associated datasets */
    public function all_individuals_ids()
    {
      $ids = $this->datasets()->cursor()->map(function($d) {
        return $d->all_individuals_ids();
      })->toArray();
      $ids = Arr::flatten($ids);
      return array_unique($ids);
    }

    public function all_voucher_ids()
    {
      $ids = $this->datasets()->cursor()->map(function($d) {
        return $d->all_voucher_ids();
      })->toArray();
      $ids = Arr::flatten($ids);
      return array_unique($ids);
    }

    public function all_locations_ids()
    {
      $ids = $this->datasets()->cursor()->map(function($d) {
        return $d->all_locations_ids();
      })->toArray();
      $ids = Arr::flatten($ids);
      return array_unique($ids);
    }
    public function all_taxons_ids()
    {
      $ids = $this->datasets()->cursor()->map(function($d) {
        return $d->all_taxons_ids();
      })->toArray();
      $ids = Arr::flatten($ids);
      return array_unique($ids);
    }


    public function getPeopleAttribute()
    {
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

    // for compatibity with $object->fullname calls
    public function getFullnameAttribute()
    {
        return $this->name;
    }

    public function getContactEmailAttribute()
    {
        return $this->admins->first()->email;
    }

    /* TAG related functions */
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


    /* GET THE UNIQUE TAXONS MODELS FOR THE IDENTIFICATIONS USED IN PROJECT DATASETS */
    public function taxons()
    {
      $ids = $this->all_taxons_ids();
      if (count($ids)==0) {
        return null;
      }
      return Taxon::select("*",DB::raw('odb_txparent(lft,120) as tx_family, odb_txparent(lft,180) as tx_genus, odb_txparent(lft,210) as tx_species'))->whereIn('taxons.id',$ids);
    }

    /* function to interact with the Count model */
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


    public function getLocationsCountAttribute()
    {
      return count($this->all_locations_ids());
    }

    public function getIndividualsCountAttribute()
    {
      return count($this->all_individuals_ids());
    }

    public function getVouchersCountAttribute()
    {
      return count($this->all_voucher_ids());
    }


    public function measurementsCount()
    {
      return null;

      return ($this->vouchers_measurements_count())+($this->individuals_measurements_count());
    }

    /*count distinct individual and voucher identification taxon at or below the species level*/
    public function getSpeciesCountAttribute()
    {
      return Taxon::whereIn('id',$this->all_taxons_ids())->where('level',">=",Taxon::getRank('species'))->count();
    }

    /* for faster display of taxon counts on datatables */
    public function getTaxonsCountAttribute()
    {
      return count($this->all_taxons_ids());
    }

    /* media count for individuals */
    public function mediaCount()
    {
      return null;

      return $this->individualsMedia()->withoutGlobalScopes()->count();
    }


    public function vouchers_measurements_count()
    {
      return null;

      return $this->voucherMeasurements()->withoutGlobalScopes()->count();
    }

    public function individuals_measurements_count()
    {
      return null;

      return $this->individualsMeasurements()->withoutGlobalScopes()->count();

    }

      public function describe_project()
      {
        $individuals = count($this->all_individuals_ids());
        $vouchers = count($this->all_voucher_ids());
        $locations = count($this->all_locations_ids());
        $taxons = count($this->all_taxons_ids());
        $species = $this->species_count;
        $media = count($this->all_media_ids());
        $datasets = $this->datasets()->count();
        $result = [
           'datasets' => $datasets,
           'individuals' => $individuals,
           'vouchers' => $vouchers,
           'locations' => $locations,
           'taxons' => $taxons,
           'species' => $species,
           'media_files' => $media,
        ];
        $result = array_filter($result,function($v) { return $v>0;});
        return $result;
      }



    /* summarize the counts of identifications per taxons.level and published vs unpublished names*/
    public function identification_summary()
    {
        $ids = $this->all_individuals_ids();
        if (count($ids)) {
          return DB::table('identifications')->join('taxons','taxon_id','=','taxons.id')->selectRaw("taxons.level, SUM(IF(taxons.author_id IS NULL,1,0)) as published,  SUM(IF(taxons.author_id IS NULL,0,1)) as unpublished, COUNT(taxons.id) as total")->where('object_type','like','%individual%')->whereIn('object_id',$ids)->groupBy('taxons.level')->get();
        } else {
          return null;
        }
    }

    public function taxonomic_summary()
    {
      $taxons_ids = $this->all_taxons_ids();
      if (count($taxons_ids)>0) {
        $ids = implode(",",$taxons_ids);
        $query = DB::select('SELECT COUNT(DISTINCT tb.fam) as families, COUNT(DISTINCT tb.genus) as genera, COUNT(DISTINCT tb.species) as species FROM (SELECT odb_txparent(taxons.lft,120) as fam,odb_txparent(taxons.lft,180) as genus, odb_txparent(taxons.lft,210) as species FROM taxons WHERE taxons.id IN('.$ids.')) as tb');
        $query  = array_map(function($value) { return (array)$value;},$query);
        return $query[0];
      } else {
        return null;
      }
    }

    public function getYearAttribute()
    {
      if ($this->last_edition_date) {
        return $this->last_edition_date->format('Y');
      }
      return null;
    }

    //get date of last edit in this dataset
    public function getLastEditionDateAttribute()
    {
      $ids = $this->datasets()->cursor()->map(function($d) {
        return $d->last_edition_date;
      })->toArray();
      if (count($ids)==0) {
        return null;
      }
      $ids = array_unique($ids);
      return array_reverse(asort($ids))[0];
    }


    /* register media modifications, this is for the project logo object */
    public function registerMediaConversions(BaseMedia $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit('crop', 200, 200)
            ->performOnCollections('logos');
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

}
