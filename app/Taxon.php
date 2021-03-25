<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Baum\Node;
use DB;
use Lang;
use App\Measurement;
use App\Identification;
use Activity;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Traits\LogsActivity;


class Taxon extends Node
{

    use LogsActivity;

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

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'object');
    }

    public function allpictures()
    {
        $ids = $this->getDescendantsAndSelf()->pluck('id')->toArray();
        return Picture::whereIn('object_id',$ids)->where('object_type','=','App\Taxon')->cursor();
    }

    public function individual_pictures()
    {
        return $this->hasManyThrough(
                      'App\Picture',
                      'App\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'object_id', // Foreign key on picture table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('identifications.object_type', 'App\Individual')->where('pictures.object_type', 'App\Individual');
    }


    public function voucher_pictures()
    {
        return $this->hasManyThrough(
                          'App\Picture',
                          'App\Identification',
                          'taxon_id', // Foreign key on identification table...
                          'object_id', // Foreign key on picture table...
                          'id', // Local key on taxon table...
                          'object_id' // Local key on identification table...
                          )->where('identifications.object_type', 'App\Voucher')->where('pictures.object_type', 'App\Voucher');
    }


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
        return $this->morphMany("App\Measurement", 'measured');
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
        return $this->belongsTo('App\Person', 'author_id');
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
        return $this->belongsTo('App\BibReference', 'bibreference_id');
    }

    public function senior()
    {
        return $this->belongsTo('App\Taxon', 'senior_id');
    }

    public function juniors()
    {
        return $this->hasMany('App\Taxon', 'senior_id');
    }


    public function individuals()
    {
        return $this->hasManyThrough(
                      'App\Individual',
                      'App\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'identification_individual_id', // Foreign key on individual table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('object_type', 'App\Individual');
    }

    public function vouchers()
    {
      return $this->hasManyThrough(
                    'App\Voucher',
                    'App\Identification',
                    'taxon_id', // Foreign key on identification table...
                    'individual_id', // Foreign key on voucher table...
                    'id', // Local key on taxon table...
                    'object_id' // Local key on identification table...
                    )->where('object_type', 'App\Individual');
    }


    // For specialists
    public function persons()
    {
        return $this->belongsToMany('App\Person');
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
        return $this->hasMany('App\TaxonExternal', 'taxon_id');
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
        return $this->hasMany("App\Identification");
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
    public function summary_counts()
    {
        return $this->morphMany("App\Summary", 'object');
    }


    public function getCount($scope="all",$scope_id=null,$target='individuals')
    {

      if (Identification::count()==0) {
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
      //get a fresh count
      if ($target=="individuals") {
        return $this->individualsCount($scope,$scope_id);
      }
      //get a fresh count
      if ($target=="measurements") {
        return $this->measurementsCount($scope,$scope_id);
      }
      //get a fresh count
      if ($target=="vouchers") {
        return $this->vouchersCount($scope,$scope_id);
      }
      if ($target=="taxons") {
        return $this->taxonsCount($scope,$scope_id);
      }
      if ($target=="pictures") {
        return $this->picturesCount($scope,$scope_id);
      }
      return 0;
    }


    /* functions to generate counts */

    public function individualsCount($scope='all',$scope_id=null)
    {
      if (('projects' == $scope or 'App\Project' == $scope) and $scope_id>0) {
          return array_sum($this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual) use($scope_id ) {
              $individual->withoutGlobalScopes()->where('project_id',$scope_id);
            }])->pluck('individual_count')->toArray());
      }
      if (('datasets' == $scope or 'App\Dataset' == $scope) and $scope_id>0) {
          $query = $this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual)  use($scope_id) {
            $individual->withoutGlobalScopes()->whereHas('measurements',function($measurement) use($scope_id) {
              $measurement->withoutGlobalScopes()->where('dataset_id','=',$scope_id);
            });}]);
          return array_sum($query->pluck('individuals_count')->toArray());
      }
      if (('locations' == $scope or 'App\Location' == $scope) and $scope_id>0) {
          $locations_ids = Location::findOrFail($scope_id)->getDescendantsAndSelf()->pluck('id')->toArray();
          $query = $this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual)  use($locations_ids) {
            $individual->withoutGlobalScopes()->whereHas('locations',function($location) use($locations_ids) {
              $location->whereIn('location_id',$locations_ids);});
            }]);
          return array_sum($query->pluck('individuals_count')->toArray());
      }
      return array_sum($this->getDescendantsAndSelf()->loadCount(['individuals' => function ($individual) {
            $individual->withoutGlobalScopes();
        }])->pluck('individuals_count')->toArray());
    }


    public function vouchersCount($scope='all',$scope_id=null)
    {
      if (('projects' == $scope or 'App\Project' == $scope) and $scope_id>0) {
        $query = $this->getDescendantsAndSelf()->loadCount(
            ['vouchers' => function ($voucher)  use($scope_id) {
                $voucher->withoutGlobalScopes()->where('project_id','=',$scope_id); } ]);
      }
      if (('datasets' == $scope or 'App\Dataset' == $scope) and $scope_id>0) {
        $query = $this->getDescendantsAndSelf()->loadCount(
            ['vouchers' => function ($voucher)  use($scope_id) {
                $voucher->withoutGlobalScopes()->whereHas('measurements',function($measurement) use($scope_id) {
                  $measurement->withoutGlobalScopes()->where('dataset_id','=',$scope_id);
                }); }]);
      }
      if (('locations' == $scope or 'App\Location' == $scope) and $scope_id>0) {
          $locations_ids = Location::findOrFail($scope_id)->getDescendantsAndSelf()->pluck('id')->toArray();
          $query = $this->getDescendantsAndSelf()->loadCount(
              ['vouchers' => function ($voucher)  use($locations_ids) {
                  $voucher->withoutGlobalScopes()->where('parent_type','=','App\Location')->whereIn('parent_id',$locations_ids);
               }]);
      }

      if (!isset($query) or null == $scope_id) {
      $query = $this->getDescendantsAndSelf()->loadCount(
          ['vouchers' => function ($voucher) { $voucher->withoutGlobalScopes(); } ]);
      }
      $count1 = array_sum($query->pluck('vouchers_count')->toArray());
      return $count1;
    }

    public function measurementsCount($scope='all',$scope_id=null)
    {
      $taxon_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
      if (('projects' == $scope or 'App\Project' == $scope) and $scope_id>0) {
        $query = Measurement::withoutGlobalScopes()->whereHasMorph('measured',['App\Individual','App\Voucher'],function($measured) use($taxon_list,$scope_id) { $measured->withoutGlobalScopes()->where('project_id',"=",$scope_id)->whereHas('identification',function($idd) use($taxon_list)  { $idd->whereIn('taxon_id',$taxon_list);});});
        //$query = $query->orWhereRaw('measured_type = "App\Taxon" AND measured_id='.$this->id);
        return $query->count();
      }
      if (('datasets' == $scope or 'App\Dataset' == $scope) and $scope_id>0) {
        $query = Measurement::withoutGlobalScopes()->whereHasMorph('measured',['App\Individual','App\Voucher'],function($measured) use($taxon_list,$scope_id) { $measured->withoutGlobalScopes()->where('dataset_id',"=",$scope_id)->whereHas('identification',function($idd) use($taxon_list)  { $idd->whereIn('taxon_id',$taxon_list);});});
        $query = $query->orWhereRaw('measured_type = "App\Taxon" AND measured_id='.($this->id).' AND dataset_id='.$scope_id);
        return $query->count();
      }
      if (('locations' == $scope or 'App\Location' == $scope) and $scope_id>0) {
        $locations_ids = Location::findOrFail($scope_id)->getDescendantsAndSelf()->pluck('id')->toArray();
        $taxon_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
        $query = Measurement::withoutGlobalScopes()->whereHasMorph('measured',['App\Individual','App\Voucher'],function($measured) use($taxon_list,$locations_ids) {
              $measured->withoutGlobalScopes()
              ->whereHas('identification',function($idd) use($taxon_list)  { $idd->whereIn('taxon_id',$taxon_list);})
              ->whereHas('locations',function($location) use($locations_ids)  { $location->whereIn('location_id',$locations_ids);});
        });
        return $query->count();
      }
      $query = Measurement::withoutGlobalScopes()->whereHasMorph('measured',['App\Individual','App\Voucher'],function($measured) use($taxon_list) { $measured->withoutGlobalScopes()->whereHas('identification',function($idd) use($taxon_list)  { $idd->whereIn('taxon_id',$taxon_list);});});
      $query = $query->orWhereRaw('measured_type = "App\Taxon" AND measured_id='.$this->id);
      return $query->count();
    }


    /* all pictures count*/
    public function picturesCount($scope='all',$scope_id=null)
    {
      $query = $this->getDescendantsAndSelf()->loadCount(
          ['individual_pictures' => function ($individual_pictures)  use($scope_id) {
              $individual_pictures->withoutGlobalScopes(); } ])->loadCount(['voucher_pictures' => function ($voucher_picture) use($scope_id) {
                  $voucher_picture->withoutGlobalScopes(); } ])->loadCount(['pictures' => function ($picture) use($scope_id) {
                      $picture->withoutGlobalScopes(); } ]);
      $count1 = array_sum($query->pluck('pictures_count')->toArray());
      $count2 = array_sum($query->pluck('voucher_pictures_count')->toArray());
      $count3 = array_sum($query->pluck('individual_pictures_count')->toArray());
      return $count1+$count2+$count3;
    }


    /* count species or below species identifications for taxon and its descendant taxons */
    public function taxonsCount($scope=null,$scope_id=null)
    {
      if ('projects' == $scope and $scope_id>0) {
          $taxons_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
          $taxonsp = Identification::with('taxon')->whereHasMorph('object',['App\Individual'],function($individual) use($scope_id){
            $individual->withoutGlobalScopes()->where('project_id','=',$scope_id);
          })->whereIn('taxon_id',$taxons_list)->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
          $taxons = array_unique($taxonsp);
      }
      if ('datasets' == $scope and $scope_id>0) {
        $taxons_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
        $taxons_list = Taxon::whereIn('id',$taxons_list)->where('level',">=",Taxon::getRank('species'))->pluck('id')->toArray();
        $taxonsp = Measurement::withoutGlobalScopes()->where('dataset_id',$scope_id)->cursor()->map(function($object) use($taxons_list) {
            if ($object->measured_type=="App\Taxon") {
              $taxon_id = $object->measured_id;
            } elseif ($object->measured_type!="App\Location") {
              $taxon_id = $object->measured->identification->taxon_id;
            }
            if (in_array($taxon_id,$taxons_list)) {
              return $taxon_id;
            }
        });
        $taxons = array_unique($taxonsp);
      }
      if ('locations' == $scope and $scope_id>0) {
          $locations_ids = Location::findOrFail($scope_id)->getDescendantsAndSelf()->pluck('id')->toArray();
          $taxons_list = $this->getDescendantsAndSelf()->pluck('id')->toArray();
          $taxons_list = Taxon::whereIn('id',$taxons_list)->where('level',">=",Taxon::getRank('species'))->pluck('id')->toArray();
          $taxonsp = Identification::with('taxon')->whereHasMorph('object',['App\Individual'],function($individual) use($scope_id){
            $individual->withoutGlobalScopes()->whereHas('locations',function($location) use($locations_ids) {
              $location->whereIn('location_id',$locations_ids);
            }
          );})->whereIn('taxon_id',$taxons_list)->distinct('taxon_id')->pluck('taxon_id')->toArray();
          $taxons = array_unique($taxonsp);
      }

      if (!isset($taxons) or null == $scope_id) {
        $taxons  = $this->getDescendantsAndSelf()->map(function($taxon) {
                  $query = $taxon->identifications()->with('taxon')->withoutGlobalScopes()->whereHas('taxon',function($taxon) { $taxon->where('level',">=",Taxon::getRank('species'));})->distinct('taxon_id')->pluck('taxon_id')->toArray();
                  return array_unique($query);
                })->toArray();
      }
      $taxons = array_unique(Arr::flatten($taxons));
      return count($taxons);
    }




}
