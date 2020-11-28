<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Baum\Node;
use DB;
use Lang;
use Activity;
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

    public function plant_pictures()
    {
        return $this->hasManyThrough(
                      'App\Picture',
                      'App\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'object_id', // Foreign key on picture table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('identifications.object_type', 'App\Plant')->where('pictures.object_type', 'App\Plant');
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
    /* required for showing counts */
    public function picturesCount()
    {

      $n1 = array_sum($this->getDescendantsAndSelf()->loadCount('pictures')->pluck('pictures_count')->toArray());
      $n2 = array_sum($this->getDescendantsAndSelf()->loadCount('voucher_pictures')->pluck('voucher_pictures_count')->toArray());
      $n3 = array_sum($this->getDescendantsAndSelf()->loadCount('plant_pictures')->pluck('plant_pictures_count')->toArray());
      return $n1+$n2+$n3;
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
        return $this->morphMany(Measurement::class, 'measured');
    }

    /* required for showing counts */
    public function measurementsCount($project_id=null)
    {
      if (null == $project_id) {
        $n1 = array_sum($this->getDescendantsAndSelf()->loadCount('plant_measurements')->pluck('plant_measurements_count')->toArray());
        $n2 = array_sum($this->getDescendantsAndSelf()->loadCount('voucher_measurements')->pluck('voucher_measurements_count')->toArray());
        $n3 = array_sum($this->getDescendantsAndSelf()->loadCount('measurements')->pluck('measurements_count')->toArray());
        return $n1+$n2+$n3;
      } else {
        $n1 = array_sum($this->getDescendantsAndSelf()->map(function($item) use($project_id) { return $item->plant_measurements()->where('project_id',$project_id)->count();})->toArray());
        $n2 = array_sum($this->getDescendantsAndSelf()->map(function($item) use($project_id) { return $item->voucher_measurements()->where('project_id',$project_id)->count();})->toArray());
        return $n1+$n2;
      }
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

    public function identified_plants()
    {
        return $this->hasMany('App\Identification')->where('object_type', 'App\Plant');
    }

    public function plants()
    {
        return $this->hasManyThrough(
                      'App\Plant',
                      'App\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'id', // Foreign key on plant table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('object_type', 'App\Plant');
    }

    public function plant_measurements()
    {
      return $this->hasManyThrough(
                    'App\Measurement',
                    'App\Identification',
                    'taxon_id', // Foreign key on identification table...
                    'measured_id', // Foreign key on plant table...
                    'id', // Local key on taxon table...
                    'object_id' // Local key on identification table...
                    )->join('plants','plants.id','=','object_id')->where('object_type', 'App\Plant')->where('measured_type','App\Plant');

    }

    public function voucher_measurements()
    {
      return $this->hasManyThrough(
                    'App\Measurement',
                    'App\Identification',
                    'taxon_id', // Foreign key on identification table...
                    'measured_id', // Foreign key on plant table...
                    'id', // Local key on taxon table...
                    'object_id' // Local key on identification table...
                    )->join('vouchers','vouchers.id','=','object_id')->where('object_type', 'App\Voucher')->where('measured_type','App\Voucher');

    }

    public function identified_vouchers()
    {
        return $this->hasMany('App\Identification')->where('object_type', 'App\Voucher');
    }

    //this is more complex than plants as they have indirect relation with plants
    public function vouchers_direct()
    {
        return $this->hasManyThrough(
                      'App\Voucher',
                      'App\Identification',
                      'taxon_id', // Foreign key on identification table...
                      'id', // Foreign key on plant table...
                      'id', // Local key on taxon table...
                      'object_id' // Local key on identification table...
                      )->where('object_type', 'App\Voucher');
    }

    //vouchers through plants
    public function plant_vouchers()
    {
      return $this->hasManyThrough(
                    'App\Voucher',
                    'App\Identification',
                    'taxon_id', // Foreign key on identification table...
                    'parent_id', // Foreign key on plant table...
                    'id', // Local key on taxon table...
                    'object_id' // Local key on identification table...
                    )->where('object_type', 'App\Plant')->where('parent_type','App\Plant');
    }


    public function vouchersCount($project_id=null)
    {
      if (null == $project_id) {
        $n1 = array_sum($this->getDescendantsAndSelf()->loadCount('vouchers_direct')->pluck('vouchers_direct_count')->toArray());
        $n2 = array_sum($this->getDescendantsAndSelf()->loadCount('plant_vouchers')->pluck('plant_vouchers_count')->toArray());
      } else {
        $n1 = array_sum($this->getDescendantsAndSelf()->map(function($item) use($project_id) { return $item->vouchers_direct()->where('project_id',$project_id)->count();})->toArray());
        $n2 = array_sum($this->getDescendantsAndSelf()->map(function($item) use($project_id) { return $item->plant_vouchers()->where('project_id',$project_id)->count();})->toArray());
      }
      return $n1+$n2;
    }

    public function plantsCount($project_id=null)
    {
      if (null == $project_id) {
        return array_sum($this->getDescendantsAndSelf()->loadCount('plants')->pluck('plants_count')->toArray());
      } else {
        return array_sum($this->getDescendantsAndSelf()->map(function($item) use($project_id) { return $item->plants()->where('project_id',$project_id)->count();})->toArray());
      }

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
    public function getPlants()
    {
        $p = $this->identifications()->where('object_type', 'App\Plant')->get()->all();

        return collect($p)->map(function ($q) {return $q->object; });
    }

    public function getVouchers()
    {
        $p = $this->identifications()->where('object_type', 'App\Voucher')->get()->all();

        return collect($p)->map(function ($q) {return $q->object; });
    }

    public function identifications()
    {
        return $this->hasMany(Identification::class);
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

}
