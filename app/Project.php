<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Facades\Image;
use Lang;
use DB;
use App\Taxon;
use Spatie\Activitylog\Traits\LogsActivity;


class Project extends Model
{
    use HasAuthLevels, LogsActivity;

    const PRIVACY_AUTH = 0;
    const PRIVACY_REGISTERED = 1;
    const PRIVACY_PUBLIC = 2;
    const PRIVACY_LEVELS = [self::PRIVACY_AUTH, self::PRIVACY_REGISTERED, self::PRIVACY_PUBLIC];

    const VIEWER = 0;
    const COLLABORATOR = 1;
    const ADMIN = 2;

    protected $fillable = ['name', 'description', 'privacy','url','details'];


    //activity log trait
    protected static $logName = 'project';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','url','details'];
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;



    public function rawLink()
    {
        return "<a href='".url('projects/'.$this->id)."'>".htmlspecialchars($this->fullname).'</a>';
    }


    public function plants()
    {
        return $this->hasMany(Plant::class);
    }


    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }


    public function plant_measurements( )
    {
      return $this->hasManyThrough(
                    'App\Measurement',
                    'App\Plant',
                    'project_id', // Foreign key on Plants table...
                    'measured_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on Plants table...
                    )->where('measurements.measured_type', 'App\Plant');
    }


    public function plant_identifications( )
    {
      return $this->hasManyThrough(
                    'App\Identification',
                    'App\Plant',
                    'project_id', // Foreign key on Plants table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on Plants table...
                    )->where('identifications.object_type', 'App\Plant');
    }

    public function plants_pictures( )
    {
      return $this->hasManyThrough(
                    'App\Picture',
                    'App\Plant',
                    'project_id', // Foreign key on Plants table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on Plants table...
                    )->where('pictures.object_type', 'App\Plant');
    }

    public function voucher_identifications( )
    {
      return $this->hasManyThrough(
                    'App\Identification',
                    'App\Voucher',
                    'project_id', // Foreign key on Plants table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on Plants table...
                    )->where('identifications.object_type', 'App\Voucher');
    }

    public function vouchers_pictures( )
    {
      return $this->hasManyThrough(
                    'App\Picture',
                    'App\Voucher',
                    'project_id', // Foreign key on Plants table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on Plants table...
                    )->where('pictures.object_type', 'App\Voucher');
    }


    public function voucher_measurements( )
    {
      return $this->hasManyThrough(
                    'App\Measurement',
                    'App\Voucher',
                    'project_id', // Foreign key on Voucher table...
                    'measured_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on Voucher table...
                    )->where('measurements.measured_type', 'App\Voucher');
    }



    // for compatibity with $object->fullname calls
    public function getFullnameAttribute()
    {
        return $this->name;
    }

    // for use in the trait edit dropdown
    public function getPrivacyLevelAttribute()
    {
        return Lang::get('levels.privacy.'.$this->privacy);
    }

    public function getContactEmailAttribute()
    {
        return $this->users()->wherePivot('access_level', '=', self::ADMIN)->first()->email;
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

    /* PROJECT LOGO */
    public function saveLogo($filepath)
    {
      $filename = 'project_'.$this->id."_logo.jpg";
      $contents = file_get_contents($filepath);
      $path = public_path('upload_pictures/'.$filename);

      $img = Image::make($contents);
      if ($img) {
        /* resize logo to maximum 150 width or height */
        if ($img->width() > $img->height()) {
            $img->widen(150)->save($path);
        } else {
            $img->heighten(150)->save($path);
        }
        //$img->save($path);
      } else {
        throw new Exception('Project logo image is invalid');
      }
    }


    /* GET THE UNIQUE TAXONS MODELS FOR THE IDENTIFICATIONS USED IN PROJECT OBJETS */
    public function taxons()
    {
      $ids = $this->taxons_ids();
      return Taxon::select("*",DB::raw('odb_txparent(lft,120) as tx_family, odb_txparent(lft,180) as tx_genus, odb_txparent(lft,210) as tx_species'))->whereIn('taxons.id',$ids);
    }

    public function taxons_ids()
    {
      $q1 =  $this->plant_identifications()->withoutGlobalScopes()->distinct('taxon_id')->pluck('taxon_id')->toArray();
      $q2 =  $this->voucher_identifications()->withoutGlobalScopes()->distinct('taxon_id')->pluck('taxon_id')->toArray();
      return array_unique(array_merge($q1,$q2));
    }

    public function locations_ids()
    {
      $q1 =  $this->plants()->withoutGlobalScopes()->distinct('location_id')->cursor()->pluck('location_id')->toArray();
      $q2 =  $this->vouchers()->withoutGlobalScopes()->distinct('parent_id')->where('parent_type','App\Location')->cursor()->pluck('parent_id')->toArray();
      return array_unique(array_merge($q1,$q2));
    }


    /* function to interact with the Count model */
    public function summary_counts()
    {
        return $this->morphMany("App\Summary", 'object');
    }


    public function getCount($scope="all",$scope_id=null,$target='plants')
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


    public function locationsCount()
    {
      return count($this->locations_ids());
    }

    public function plantsCount()
    {
       return $this->plants()->withoutGlobalScopes()->count();
    }

    public function vouchersCount()
    {
        return $this->vouchers()->withoutGlobalScopes()->count();
    }
    public function measurementsCount()
    {
      return ($this->vouchersMeasurementsCount())+($this->plantsMeasurementsCount());
    }

    /*count distinct plant and voucher identification taxon at or below the species level*/
    public function count_taxons($what=null)
    {
      $taxonsp = [];
      if ($what == null or $what=='plants') {
        $taxonsp = $this->plant_identifications()->withoutGlobalScopes()->with('taxon')->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
      }
      $taxonsv = [];
      if ($what == null or $what=='vouchers') {
        $taxonsv = $this->voucher_identifications()->withoutGlobalScopes()->with('taxon')->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
      }
      $taxons = array_unique(array_merge($taxonsp,$taxonsv));
      return count($taxons);
    }


    public function taxonsCount()
    {
      return $this->count_taxons();
    }

    /* pictures of plants and vouchers only */
    public function picturesCount()
    {
      $picturesp = $this->plants_pictures()->withoutGlobalScopes()->count();
      $picturesv = $this->vouchers_pictures()->withoutGlobalScopes()->count();
      return ($picturesp+$picturesv);
    }


    public function vouchersTaxonsCount()
    {
      return $this->voucher_identifications()->withoutGlobalScopes()->distinct('taxon_id')->count();
    }
    public function plantsTaxonsCount()
    {
      return $this->plant_identifications()->withoutGlobalScopes()->distinct('taxon_id')->count();
    }


    public function vouchersMeasurementsCount()
    {
      return $this->voucher_measurements()->withoutGlobalScopes()->count();
    }

    public function plantsMeasurementsCount()
    {
      return $this->plant_measurements()->withoutGlobalScopes()->count();

    }

    public function datasetIDS()
    {
      $query = DB::select('(SELECT DISTINCT dataset_id FROM measurements INNER JOIN plants ON plants.id=measurements.measured_id WHERE measured_type="App\\\Plant" AND plants.project_id='.$this->id.') UNION (SELECT DISTINCT dataset_id FROM measurements INNER JOIN vouchers ON vouchers.id=measurements.measured_id WHERE measured_type="App\\\Voucher" AND vouchers.project_id='.$this->id.")");
      $query  = array_map(function($value) { return (array)$value;},$query);
      return $query;
    }

    public function datasetsCount()
    {
      return count($this->datasetIDS());
    }

    /* summarize the counts of identifications per taxons.level and published vs unpublished names*/
    public function identification_summary()
    {
        $query = DB::select('SELECT tb.level, SUM(CASE tb.status  WHEN 0 THEN 1 ELSE 0 END) AS unpublished, SUM(CASE tb.status  WHEN 1 THEN 1 ELSE 0 END) AS published, count(tb.taxon_id) as total FROM (SELECT identifications.taxon_id,taxons.level, IF(taxons.author_id IS NULL,1,0) as status FROM plants RIGHT JOIN identifications ON plants.id=identifications.object_id LEFT JOIN taxons ON taxons.id=identifications.taxon_id WHERE identifications.object_type="App\\\Plant" AND project_id='.$this->id.' AND (identifications.taxon_id IS NOT NULL) UNION SELECT identifications.taxon_id,taxons.level,IF(taxons.author_id IS NULL,1,0) as status FROM vouchers RIGHT JOIN identifications ON vouchers.id=identifications.object_id LEFT JOIN taxons ON taxons.id=identifications.taxon_id WHERE identifications.object_type="App\\\Voucher" AND project_id='.$this->id.' AND (identifications.taxon_id IS NOT NULL)) as tb WHERE tb.taxon_id>0 GROUP BY tb.level');
      return $query;
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
