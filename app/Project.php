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
use Spatie\Activitylog\Traits\LogsActivity;


class Project extends Model
{
    use HasAuthLevels, LogsActivity;

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
    // this could be implemented as EloquentHasManyDeep but is slower
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


    /* SUMMARY FUNCTIONS FOR INFORMING WHAT THE PROJECT IS ABOUT */
    /* counts must be shown regardless of permission, so unatuhorized users can see how many things the project have */
    /* hence using the DB raw queries */

    public function plants_public_count()
    {
       return $this->plants()->withoutGlobalScopes()->count();
    }

    public function vouchers_public_count()
    {
        return $this->vouchers()->withoutGlobalScopes()->count();
    }

    public function vouchers_public_taxons_count()
    {
      return $this->voucher_identifications()->withoutGlobalScopes()->distinct('taxon_id')->count();
    }
    public function plants_public_taxons_count()
    {
      return $this->plant_identifications()->withoutGlobalScopes()->distinct('taxon_id')->count();
    }


    public function vouchers_public_measurements_count()
    {
      return $this->voucher_measurements()->withoutGlobalScopes()->count();
    }

    public function plants_public_measurements_count()
    {
      return $this->plant_measurements()->withoutGlobalScopes()->count();

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
