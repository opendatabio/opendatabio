<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Baum\Node;
use DB;
use Lang;
use App\Models\Measurement;
use App\Models\Identification;
use Activity;
use Illuminate\Support\Arr;

use Spatie\Activitylog\Traits\LogsActivity;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Taxon extends Node implements HasMedia
{

    use InteractsWithMedia, LogsActivity;

    public $table = "taxons";
    protected $leftColumnName = 'lft';
    protected $rightColumnName = 'rgt';
    protected $depthColumnName = 'depth';


    protected $fillable = ['name', 'level', 'valid', 'validreference', 'senior_id', 'author', 'author_id',
                'bibreference', 'bibreference_id', 'parent_id', 'notes', ];

    protected $appends = ['family'];

    //activity log trait (parent, uc and geometry are logged in controller)
    protected static $logName = 'taxon';
    protected static $recordEvents = ['updated','deleted'];
    protected static $ignoreChangedAttributes = ['updated_at','lft','rgt','depth'];
    protected static $logFillable = true;
    //$logAttributes = ['name','altitude','adm_level','datum','x','y','startx','starty','notes'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    // for use when receiving this as part of a morph relation
    // TODO: maybe can be changed to get_class($p)?
    public function getTypenameAttribute()
    {
        return 'taxons';
    }

    public function rawLink()
    {
        return "<em><a href='".url('taxons/'.$this->id)."'>".htmlspecialchars($this->qualifiedFullname).'</a></em>';
    }

    /* similarly to Location, this will be the root of the taxon tree */
    public function scopeNoRoot($query)
    {
        return $query->where('level', '<>', -1);
    }

    public static function lifeRoot()
    {
        return self::where('level', -1)->get()->first();
    }


    // for use in selects, lists the most common tax levels
    public static function TaxLevels()
    {
        return [0, 10, 30, 40, 60, 70, 80, 90, 100, 120, 130, 150, 180, 190, 210, 220, 240, 270, -100];
    }

    public function newQuery($excludeDeleted = true)
    {
        // includes the full name of a taxon in all queries
        return parent::newQuery($excludeDeleted)->addSelect(
                '*',
                DB::raw('odb_txname(taxons.name, taxons.level, taxons.parent_id) as fullname')
            );
    }

    public function mediaDescendantsAndSelf()
    {
        $ids =  self::select("id")->where('lft',">=",$this->lft)->where('lft',"<=",$this->rgt)->pluck('id')->toArray();
        return Media::whereIn('model_id',$ids)->where('model_type','=','App\Models\Taxon');
    }

    public function individualsMedia()
    {
        return $this->hasManyThrough(
                      'App\Models\Media',
                      'App\Models\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'model_id', // Foreign key on media table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('identifications.object_type', 'App\Models\Individual')->where('media.model_type', 'App\Models\Individual');
    }


    /* THIS MAY BE ELIMIATED AND MEDIA VOUCHER SHOULD BE LINKED TO INDIVIDUAL WITH voucher_id as custom property
    public function voucherMedia()
    {
        return $this->hasManyThrough(
                          'App\Models\Media',
                          'App\Models\Identification',
                          'taxon_id', // Foreign key on identification table...
                          'model_id', // Foreign key on media table...
                          'id', // Local key on taxon table...
                          'object_id' // Local key on identification table...
                          )->where('identifications.object_type', 'App\Models\Voucher')->where('media.model_type', 'App\Models\Voucher');
    }
    */

    public function getLevelNameAttribute()
    {
        return Lang::get('levels.tax.'.$this->level).
            ($this->author_person ? ' ('.Lang::get('messages.unpublished').')' : '');
    }

    public function getQualifiedFullnameAttribute()
    {
        return ($this->valid ? '' : '**').$this->fullname;
    }

    public function identifiableName()
    {
        return $this->getQualifiedFullnameAttribute();
    }

    public function measurements()
    {
        return $this->morphMany("App\Models\Measurement", 'measured');
    }




    public function setFullnameAttribute($value)
    {
        // Full names have only the first letter capitalized
        $value = ucfirst(strtolower($value));

        if ($this->level <= 200) {
            $this->name = $value;
        }
        if ($this->level >= 210) { // sp. or below, strips evertyhing before the last space
            $this->name = trim(substr($value, strrpos($value, ' ') - strlen($value)));
        }
    }

    // returns rank numbers from common abbreviations
    public static function getRank($rank)
    {
        $rank = strtolower($rank);
        switch ($rank) {
                case 'kingdom':
                    return 0;
                case 'subkingdom':
                case 'subkingd.':
                    return 10;
                case 'div.':
                case 'phyl.':
                case 'phylum':
                case 'division':
                        return 30;
                case 'subdiv.':
                case 'subphyl.':
                case 'subphylum':
                case 'subdivision':
                        return 40;
                case 'cl.':
                case 'class':
                        return 60;
                case 'subcl.':
                case 'subclass':
                        return 70;
                case 'superord.':
                case 'superorder':
                        return 80;
                case 'ord.':
                case 'order':
                        return 90;
                case 'subord.':
                case 'suborder':
                        return 100;
                case 'fam.':
                case 'family':
                        return 120;
                case 'subfam.':
                case 'subfamily':
                        return 130;
                case 'tr.':
                case 'tribe':
                        return 150;
                case 'gen.':
                case 'genus':
                        return 180;
                case 'subg.':
                case 'subgenus':
                case 'sect.':
                case 'section':
                        return 190;
                case 'sp.':
                case 'spec.':
                case 'species':
                        return 210;
                case 'subsp.':
                case 'subspecies':
                        return 220;
                case 'var.':
                case 'variety':
                        return 240;
                case 'f.':
                case 'fo.':
                case 'form':
                    return 270;
                case 'clade':
                    return -100;
                default:
                    return null;
                }
    }

    public function author_person()
    {
        return $this->belongsTo('App\Models\Person', 'author_id');
    }

    public function getAuthorSimpleAttribute()
    {
        if ($this->author) {
            return $this->author;
        }
        if ($this->author_person) {
            return $this->author_person->abbreviation;
        }
    }

    public function getFullnameWithAuthor()
    {
      return $this->fullname." ".$this->authorSimple;
    }

    public function getBibreferenceSimpleAttribute()
    {
        if ($this->bibreference) {
            return $this->bibreference;
        }
        if ($this->reference) {
            return $this->reference->bibtex;
        }
    }

    public function reference()
    {
        return $this->belongsTo('App\Models\BibReference', 'bibreference_id');
    }

    public function senior()
    {
        return $this->belongsTo('App\Models\Taxon', 'senior_id');
    }

    public function juniors()
    {
        return $this->hasMany('App\Models\Taxon', 'senior_id');
    }


    public function individuals()
    {
        return $this->hasManyThrough(
                      'App\Models\Individual',
                      'App\Models\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'identification_individual_id', // Foreign key on individual table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('object_type', 'App\Models\Individual');
    }

    public function vouchers()
    {
      return $this->hasManyThrough(
                    'App\Models\Voucher',
                    'App\Models\Identification',
                    'taxon_id', // Foreign key on identification table...
                    'individual_id', // Foreign key on voucher table...
                    'id', // Local key on taxon table...
                    'object_id' // Local key on identification table...
                    )->where('object_type', 'App\Models\Individual');
    }


    // For specialists
    public function persons()
    {
        return $this->belongsToMany('App\Models\Person');
    }

    public function scopeValid($query)
    {
        return $query->where('valid', '=', 1);
    }

    public function scopeLeaf($query)
    {
        // partially reimplemented from Etrepat/Baum
        $grammar = $this->getConnection()->getQueryGrammar();
        $rgtCol = $grammar->wrap($this->getQualifiedRightColumnName());
        $lftCol = $grammar->wrap($this->getQualifiedLeftColumnName());

        return $query->where('level', '>=', 210)->whereRaw($rgtCol.' - '.$lftCol.' = 1');
    }

    // Functions for handling API keys
    public function setapikey($name, $reference)
    {
        $refs = $this->externalrefs()->where('name', $name);
        $oldref = $this->externalrefs()->select(['name','reference'])->where('name',$name);
        if ($reference) { // if reference is set...
            if ($refs->count()) { // and we have one already, update it
                $refs->update(['reference' => $reference]);
            } else { // none already, so create one
                $this->externalrefs()->create([
                    'name' => $name,
                    'reference' => $reference,
                ]);
            }
        } else { // no reference set
            if ($refs->count()) { // if there was one, delete it
                $refs->delete();
            }
        }

        $newref = $this->externalrefs()->select(['name','reference'])->where('name',$name);

        //if there is change, then log
        if ($newref->count() and $oldref->count()) {
          $newref = $newref->first()->toArray();
          $oldref = $oldref->first()->toArray();
          $old = array_diff_assoc($oldref,$newref);
          $new= array_diff_assoc($newref,$oldref);
          if (count($old)>0 || count($new)>0 ) {
            $tolog = array('attributes' => $newref, 'old' => $oldref);
            activity('taxon')
              ->performedOn($this)
              ->withProperties($tolog)
              ->log('taxon ExternalAPIs changed');
            }
        }
    }

    public function externalrefs()
    {
        return $this->hasMany('App\Models\TaxonExternal', 'taxon_id');
    }

    public function getMobotAttribute()
    {
        foreach ($this->externalrefs as $ref) {
            if ('Mobot' == $ref->name) {
                return $ref->reference;
            }
        }
    }

    public function getIpniAttribute()
    {
        foreach ($this->externalrefs as $ref) {
            if ('IPNI' == $ref->name) {
                return $ref->reference;
            }
        }
    }

    public function getMycobankAttribute()
    {
        foreach ($this->externalrefs as $ref) {
            if ('Mycobank' == $ref->name) {
                return $ref->reference;
            }
        }
    }

    public function getGbifAttribute()
    {
        foreach ($this->externalrefs as $ref) {
            if ('GBIF' == $ref->name) {
                return $ref->reference;
            }
        }
    }

    public function getZoobankAttribute()
    {
        foreach ($this->externalrefs as $ref) {
            if ('ZOOBANK' == $ref->name) {
                return $ref->reference;
            }
        }
    }


    public function identifications()
    {
        return $this->hasMany("App\Models\Identification");
    }


    // returns: mixed. May be string if not found in DB, or int (id) if found, or null if no query possible
    public static function getParent($name, $rank, $family)
    {
        switch (true) {
            case $rank <= 120:
                return null; // Family or above, MOBOT has no parent information
            case $rank > 120 and $rank <= 180: // sub-family to genus, parent should be a family
                if (is_null($family)) {
                    return null;
                }
                $searchstr = $family;
                break;
            case $rank > 180 and $rank <= 210: // species, parent should be genus
                $searchstr = substr($name, 0, strpos($name, ' '));
                break;
            case $rank > 210 and $rank <= 280: // subsp, var or form, parent should be a species
                preg_match('/^(\w+\s\w+)\s/', $name, $match);
                $searchstr = trim($match[1]);
                break;
            default:
                return null;
            }
        $toparent = self::whereRaw('odb_txname(taxons.name, taxons.level, taxons.parent_id) = ?', [$searchstr]);
        if ($toparent->count()) {
            return [$toparent->first()->id, $searchstr];
        } else {
            return $searchstr;
        }
    }

      public function getParentNameAttribute()
      {
        if ($this->parent) {
          return $this->parent->fullname;
        }
        return null;
      }

    public function getFamilyAttribute()
    {
        if ($this->level < $this->getRank('family')) {
            return '';
        }
        if ($this->getRank('family') == $this->level) {
            return $this->name;
        }
        // else
        $parent = $this->parent;
        while ($parent->parent and $parent->level > $this->getRank('family')) {
            $parent = $parent->parent;
        }

        return $parent->name;
    }

    public function parentByLevel($level)
    {
      return  DB::table('taxons')->where('lft',"<=",$this->lft)->where('rgt',">=",$this->lft)->where('level',$level)->get();
      // code...
    }


    public function getGenusAttribute()
    {
        if ($this->level < $this->getRank('genus')) {
            return '';
        }
        if ($this->getRank('genus') == $this->level) {
            return $this->name;
        }
        // else
        $parent = $this->parent;
        while ($parent->parent and $parent->level > $this->getRank('genus')) {
            $parent = $parent->parent;
        }

        return $parent->name;
    }

    public function getSpeciesAttribute()
    {
        if ($this->level >= $this->getRank('species')) {
            return $this->fullname;
        }
        return null;
    }

    public function familyRawLink()
    {
        if ($this->level < 120) {
            return '';
        }
        if (120 == $this->level) {
            return $this->name;
        }
        // else
        $parent = $this->parent;
        while ($parent->parent and $parent->level > 120) {
            $parent = $parent->parent;
        }

        return $parent->rawLink();
    }


    public function familyModel()
    {
      $id = $this->getAncestorsAndSelf()->where('level',$this->getRank('family'))->pluck('id')->toArray();
      if ($id) {
        return Taxon::whereIn('id',$id)->first();
      } else {
        return null;
      }
    }

    public function genusModel()
    {
      $id = $this->getAncestorsAndSelf()->where('level',$this->getRank('genus'))->pluck('id')->toArray();
      if ($id) {
        return Taxon::whereIn('id',$id)->first();
      } else {
        return null;
      }
    }

    public function references()
    {
        return $this->belongsToMany(BibReference::class,'taxon_bibreference')->withTimestamps();
    }



    /* FUNCTIONS TO INTERACT WITH THE COUNT MODEL */
    public function summaryCounts()
    {
        return $this->morphMany("App\Models\Summary", 'object');
    }


    public function getCount($scope="all",$scopeId=null,$target='individuals')
    {

      if (Identification::count()==0) {
        return 0;
      }

      $query = $this->summaryCounts()->where('scope_type',"=",$scope)->where('target',"=",$target);
      if (null !== $scopeId) {
        $query = $query->where('scope_id',"=",$scopeId);
      } else {
        $query = $query->whereNull('scope_id');
      }
      if ($query->count()) {
        return $query->first()->value;
      }
      //get a fresh count
      if ($target=="individuals") {
        return $this->individualsCount($scope,$scopeId);
      }
      //get a fresh count
      if ($target=="measurements") {
        return $this->measurementsCount($scope,$scopeId);
      }
      //get a fresh count
      if ($target=="vouchers") {
        return $this->vouchersCount($scope,$scopeId);
      }
      if ($target=="taxons") {
        return $this->taxonsCount($scope,$scopeId);
      }
      if ($target=="media") {
        return $this->all_media_count();
      }
      return 0;
    }


    /* functions to generate counts */

    public function individualsCount($scope='all',$scopeId=null)
    {
      $sql = "SELECT DISTINCT(individuals.id) FROM individuals,identifications,taxons where  identifications.object_id=individuals.id AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
      if (('projects' == $scope or 'App\Models\Project' == $scope) and $scopeId>0) {
        $sql .= " AND individuals.project_id=".$scope_id;
      }
      if (('datasets' == $scope or 'App\Models\Dataset' == $scope) and $scopeId>0) {
        $sql = "SELECT DISTINCT(measurements.measured_id) FROM measurements,identifications,taxons where  identifications.object_id=measurements.measured_id AND (identifications.object_type LIKE '%individual%') AND (measurements.measured_type LIKE %individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
        $sql .= " AND measurements.dataset_id=".$scope_id;
      }
      if (('locations' == $scope or 'App\Models\Location' == $scope) and $scopeId>0) {
        $location = Location::withoutGeom()->findOrFail($scopeId);
        $sql = "SELECT DISTINCT(individuals.id) FROM individuals,identifications,taxons,individual_location,locations where individual_location.individual_id=individuals.id AND locations.id=individual_location.location_id AND identifications.object_id=individuals.id AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt." AND locations.lft>=".$location->lft." AND locations.lft<=".$location->rgt;
      }
      $query = DB::select($sql);
      return count($query);
    }


    public function vouchersCount($scope='all',$scopeId=null)
    {
      $sql = "SELECT DISTINCT(vouchers.id) FROM vouchers,individuals,identifications,taxons where vouchers.individual_id=individuals.id AND identifications.object_id=individuals.id AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id  AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
      if (('projects' == $scope or 'App\Models\Project' == $scope) and $scopeId>0) {
        $sql .= " AND individuals.project_id=".$scope_id;
      }
      if (('datasets' == $scope or 'App\Models\Dataset' == $scope) and $scopeId>0) {
        $sql = "SELECT DISTINCT(vouchers.measured_id) FROM individuals,vouchers,measurements,identifications,taxons where identifications.object_id=individuals.id AND vouchers.individual_id=individuals.id AND identifications.object_id=measurements.measured_id AND (identifications.object_type LIKE '%individual%') AND (measurements.measured_type LIKE %individual%') AND identifications.taxon_id=taxons.id  AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
        $sql .= " AND measurements.dataset_id=".$scope_id;
      }
      if (('locations' == $scope or 'App\Models\Location' == $scope) and $scopeId>0) {
        $location = Location::withoutGeom()->findOrFail($scopeId);
        $sql = "SELECT DISTINCT(vouchers.id) FROM vouchers, individuals,identifications,taxons,individual_location,locations where vouchers.individual_id=individuals.id AND individual_location.individual_id=individuals.id AND locations.id=individual_location.location_id AND identifications.object_id=individuals.id AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt." AND locations.lft>=".$location->lft." AND locations.lft<=".$location->rgt;
      }
      $query = DB::select($sql);
      return count($query);
    }

    public function measurementsCount($scope='all',$scopeId=null)
    {
      $sql = "SELECT DISTINCT(measurements.id) FROM individuals,measurements,identifications,taxons where  identifications.object_id=individuals.id AND identifications.object_id=measurements.measured_id AND (identifications.object_type LIKE '%individual%') AND (measurements.measured_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
      if (('projects' == $scope or 'App\Models\Project' == $scope) and $scopeId>0) {
        $sql .= " AND individuals.project_id=".$scope_id;
        $query = DB::select($sql);
        return count($query);
      }
      if (('datasets' == $scope or 'App\Models\Dataset' == $scope) and $scopeId>0) {
        $sql .= " AND measurements.dataset_id=".$scope_id;
        $query = DB::select($sql);
        return count($query);
      }
      if (('locations' == $scope or 'App\Models\Location' == $scope) and $scopeId>0) {
        $sql = "SELECT DISTINCT(measurements.id) FROM measurements,identifications,taxons,individual_location where  identifications.object_id=individuals.id AND identifications.object_id=measurements.measured_id AND (identifications.object_type LIKE '%individual%') AND (measurements.measured_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND individual_location.individual_id=identifications.object_id AND locations.id=individual_location.location_id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt." AND locations.lft>=".$location->lft." AND locations.lft<=".$location->rgt;
        $query = DB::select($sql);
        return count($query);
      }
      $sql = "SELECT DISTINCT(measurements.id) FROM individuals,measurements,identifications,taxons where  identifications.object_id=individuals.id AND identifications.object_id=measurements.measured_id AND (identifications.object_type LIKE '%individual%') AND (measurements.measured_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
      $query = DB::select($sql);
      return count($query)+$this->measurements()->count();
    }


    /* all media count*/
    public function all_media_count()
    {
      //media linked to individuals identified by the taxon or taxon descendants
      $sql = "SELECT DISTINCT(media.id) FROM individuals,media,identifications,taxons where  identifications.object_id=individuals.id AND media.model_id=individuals.id AND (media.model_type LIKE '%individual%') AND (identifications.object_type LIKE '%individual%') AND identifications.taxon_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
      $query = DB::select($sql);

      //media linked to taxon or descendants
      $sql = "SELECT DISTINCT(media.id) FROM media,taxons where (media.model_type LIKE '%taxons%') AND media.model_id=taxons.id AND taxons.lft>=".$this->lft." AND taxons.lft<=".$this->rgt;
      $query2 = DB::select($sql);

      //return unique count
      $query = array_unique(array_merge($query,$query2));
      return count($query);
    }


    /* count species or below species identifications for taxon and its descendant taxons */
    public function taxonsCount($scope=null,$scopeId=null)
    {
      if ('projects' == $scope and $scopeId>0) {
          $taxons_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
          $taxonsp = Identification::with('taxon')->whereHasMorph('object',['App\Models\Individual'],function($individual) use($scopeId){
            $individual->withoutGlobalScopes()->where('project_id','=',$scopeId);
          })->whereIn('taxon_id',$taxons_list)->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
          $taxons = array_unique($taxonsp);
      }
      if ('datasets' == $scope and $scopeId>0) {
        $taxons_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
        $taxons_list = Taxon::whereIn('id',$taxons_list)->where('level',">=",Taxon::getRank('species'))->pluck('id')->toArray();
        $taxonsp = Measurement::withoutGlobalScopes()->where('dataset_id',$scopeId)->cursor()->map(function($object) use($taxons_list) {
            if ($object->measured_type=="App\Models\Taxon") {
              $taxon_id = $object->measured_id;
            } elseif ($object->measured_type!="App\Models\Location") {
              $taxon_id = $object->measured->identification->taxon_id;
            }
            if (in_array($taxon_id,$taxons_list)) {
              return $taxon_id;
            }
        });
        $taxons = array_unique($taxonsp);
      }
      if ('locations' == $scope and $scopeId>0) {
          $locations_ids = Location::findOrFail($scopeId)->getDescendantsAndSelf()->pluck('id')->toArray();
          $taxons_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
          $taxons_list = Taxon::whereIn('id',$taxons_list)->where('level',">=",Taxon::getRank('species'))->pluck('id')->toArray();
          $taxonsp = Identification::with('taxon')->whereHasMorph('object',['App\Models\Individual'],function($individual) use($scopeId){
            $individual->withoutGlobalScopes()->whereHas('locations',function($location) use($locations_ids) {
              $location->whereIn('location_id',$locations_ids);
            }
          );})->whereIn('taxon_id',$taxons_list)->distinct('taxon_id')->pluck('taxon_id')->toArray();
          $taxons = array_unique($taxonsp);
      }

      if (!isset($taxons) or null == $scopeId) {
        $taxons  = $this->getDescendantsAndSelf()->map(function($taxon) {
                  $query = $taxon->identifications()->with('taxon')->withoutGlobalScopes()->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
                  return array_unique($query);
                })->toArray();
      }
      $taxons = array_unique(Arr::flatten($taxons));
      return count($taxons);
    }





    /* register media modifications */
    public function registerMediaConversions(BaseMedia $media = null): void
    {

        $this->addMediaConversion('thumb')
            ->fit('crop', 200, 200)
            ->performOnCollections('images');

        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->extractVideoFrameAtSecond(5)
            ->performOnCollections('videos');
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }


}
