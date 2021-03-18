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
use CodeInc\StripAccents\StripAccents;


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

    protected $fillable = ['name', 'description', 'privacy','policy','details','license','title'];


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


    public function individuals()
    {
        return $this->hasMany(Individual::class);
    }


    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function individual_locations( )
    {
      return $this->hasManyThrough(
                    'App\IndividualLocation',
                    'App\Individual',
                    'project_id', // Foreign key on individuals table...
                    'individual_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on individuals table...
                    );
    }


    public function individual_measurements( )
    {
      return $this->hasManyThrough(
                    'App\Measurement',
                    'App\Individual',
                    'project_id', // Foreign key on individuals table...
                    'measured_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on individuals table...
                    )->where('measurements.measured_type', 'App\Individual');
    }


    public function individual_identifications( )
    {
      return $this->hasManyThrough(
                    'App\Identification',
                    'App\Individual',
                    'project_id', // Foreign key on individuals table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'identification_individual_id' // Local key on individuals table...
                    )->where('identifications.object_type', 'App\Individual');
    }

    public function individuals_pictures( )
    {
      return $this->hasManyThrough(
                    'App\Picture',
                    'App\Individual',
                    'project_id', // Foreign key on individuals table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on individuals table...
                    )->where('pictures.object_type', 'App\Individual');
    }

    public function voucher_identifications( )
    {
      return $this->hasManyThrough(
                    'App\Identification',
                    'App\Voucher',
                    'project_id', // Foreign key on individuals table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'individual_id' // Local key on voucher table...
                    )->where('identifications.object_type', 'App\Individual');
    }

    public function vouchers_pictures( )
    {
      return $this->hasManyThrough(
                    'App\Picture',
                    'App\Voucher',
                    'project_id', // Foreign key on individuals table...
                    'object_id', // Foreign key on Measurements table...
                    'id', // Local key on Project table...
                    'id' // Local key on individuals table...
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


    /* authorship */
    public function authors()
    {
      return $this->morphMany(Collector::class, 'object')->with('person');
    }

    public function author_first()
    {
        return $this->authors()->where('main',1);
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
      $ids = $this->individual_identifications()->withoutGlobalScopes()->distinct('taxon_id')->pluck('taxon_id')->toArray();
      return array_unique($ids);
    }



    public function locations_ids()
    {
      $ids = $this->individual_locations()->distinct('location_id')->pluck('location_id')->toArray();
      return array_unique($ids);
    }


    /* function to interact with the Count model */
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


    public function locationsCount()
    {
      return $this->individual_locations()->distinct('location_id')->count();
    }

    public function individualsCount()
    {
       return $this->individuals()->withoutGlobalScopes()->count();
    }

    public function vouchersCount()
    {
        return $this->vouchers()->withoutGlobalScopes()->count();
    }
    public function measurementsCount()
    {
      return ($this->vouchersMeasurementsCount())+($this->individualsMeasurementsCount());
    }

    /*count distinct individual and voucher identification taxon at or below the species level*/
    public function speciesCount()
    {
      $taxonsp = $this->individual_identifications()->withoutGlobalScopes()->with('taxon')->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
      $taxons = array_unique($taxonsp);
      return count($taxons);
    }

    /* for faster display of taxon counts on datatables */
    public function taxonsCount()
    {
      $count_level = Taxon::getRank('species');
      return $this->summary_scopes()->whereHasMorph('object',['App\Taxon'],function($object) use($count_level) { $object->where('level','>=',$count_level);})->where('object_type','App\Taxon')->selectRaw("DISTINCT object_id")->cursor()->count();
    }

    /* pictures of individuals and vouchers only */
    public function picturesCount()
    {
      $picturesp = $this->individuals_pictures()->withoutGlobalScopes()->count();
      $picturesv = $this->vouchers_pictures()->withoutGlobalScopes()->count();
      return ($picturesp+$picturesv);
    }


    public function vouchersTaxonsCount()
    {
      return $this->voucher_identifications()->withoutGlobalScopes()->distinct('taxon_id')->count();
    }
    public function individualsTaxonsCount()
    {
      return $this->individual_identifications()->withoutGlobalScopes()->distinct('taxon_id')->count();
    }


    public function vouchersMeasurementsCount()
    {
      return $this->voucher_measurements()->withoutGlobalScopes()->count();
    }

    public function individualsMeasurementsCount()
    {
      return $this->individual_measurements()->withoutGlobalScopes()->count();

    }

    public function datasetIDS()
    {
      $query = DB::select('SELECT DISTINCT tb.dataset_id FROM ((SELECT DISTINCT dataset_id FROM measurements INNER JOIN individuals ON individuals.id=measurements.measured_id WHERE measured_type="App\\\Individual" AND individuals.project_id='.$this->id.') UNION (SELECT DISTINCT dataset_id FROM measurements INNER JOIN vouchers ON vouchers.id=measurements.measured_id WHERE measured_type="App\\\Voucher" AND vouchers.project_id='.$this->id.')) as tb');
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
        $query = DB::select('SELECT tb.level, SUM(CASE tb.status  WHEN 0 THEN 1 ELSE 0 END) AS unpublished, SUM(CASE tb.status  WHEN 1 THEN 1 ELSE 0 END) AS published, count(tb.taxon_id) as total FROM (SELECT identifications.taxon_id,taxons.level, IF(taxons.author_id IS NULL,1,0) as status FROM individuals RIGHT JOIN identifications ON individuals.id=identifications.object_id LEFT JOIN taxons ON taxons.id=identifications.taxon_id WHERE identifications.object_type="App\\\Individual" AND project_id='.$this->id.' AND (identifications.taxon_id IS NOT NULL) UNION SELECT identifications.taxon_id,taxons.level,IF(taxons.author_id IS NULL,1,0) as status FROM vouchers RIGHT JOIN identifications ON vouchers.individual_id=identifications.object_id LEFT JOIN taxons ON taxons.id=identifications.taxon_id WHERE identifications.object_type="App\\\Individual" AND project_id='.$this->id.' AND (identifications.taxon_id IS NOT NULL)) as tb WHERE tb.taxon_id>0 GROUP BY tb.level');
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
      $title = isset($this->title) ? $this->title  : $this->name;
      if ($for_dt) {
        $title = "<a href='".url('projects/'.$this->id)."'>".htmlspecialchars($title).'</a>';
      }
      if (preg_match("/CC0/i",$license)) {
        $license = "Public domain - CC0";
      }
      if ($author != null) {
        $citation = $author." (".$year.").  <strong>".$title."</strong>. Occurrence data. Version: ".$version.". License: ".$license;
      } else {
        $citation = "<strong>".$title."</strong>. Occurrence data. Version: ".$version.". License: ".$license;
      }
      if (!$for_dt) {
        $url =  url('project/'.$this->id);
        $citation .= '. From '.$url.', accessed '.$when.".";
      }
      return $citation;
    }

    public function getBibtexAttribute()
    {
      if ($this->individuals()->withoutGlobalScopes()->count() ==0 ) {
        return null;
      }
      $bibkey = preg_replace('[,| |\\.|-|_]','',StripAccents::strip( (string) $this->name ))."_".$this->last_edition_date->format("Y");
      $version = $this->last_edition_date->format("Y-m-d");
      $license = (null != $this->license) ? $this->license : 'Not defined, restrictions may apply.';
      if (preg_match("/CC0/i",$license)) {
        $license = "Public domain - CC0";
      }
      $bib =  [
         'title' => $this->title,
         'year' => $this->last_edition_date->format("Y"),
         'author' => $this->all_authors,
         'howpublished' => "url\{".url('project/'.$this->id)."}",
         'version' => $version,
         'license' => $license,
         'note' => 'Species Occurrence data. Version: '.$version.'. License: '.$license.". Accessed: ".today()->format("Y-m-d"),
         'url' => "{".url('project/'.$this->id)."}",
      ];
      return "@misc{".$bibkey.",\n".json_encode($bib,JSON_PRETTY_PRINT);
    }

    //get date of last edit in this dataset
    public function getLastEditionDateAttribute()
    {
      if ($this->individuals()->withoutGlobalScopes()->count() ==0 ) {
        return null;
      }
      $lastdate = $this->individuals()->withoutGlobalScopes()->select('individuals.updated_at')->orderBy('updated_at','desc')->first()->updated_at;
      return $lastdate;
    }


}
