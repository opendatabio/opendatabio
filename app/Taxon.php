<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Baum\Node;
use DB;
use Log;

class Taxon extends Node
{
    // for use when receiving this as part of a morph relation
    // TODO: maybe can be changed to get_class($p)?
    public function getTypenameAttribute() { return "plants"; }
        protected $fillable = ['name', 'level', 'valid', 'validreference', 'senior_id', 'author', 'author_id',
                'bibreference', 'bibreference_id', 'parent_id', 'notes'];
        // for use in selects, lists the most common tax levels
        static public function TaxLevels() { 
                return [0, 30, 60, 70, 90, 100, 120, 130, 150, 180, 210, 220, 240, 270]; 
        }
        public function newQuery($excludeDeleted = true)
        {
            // includes the full name of a taxon in all queries
            return parent::newQuery($excludeDeleted)->addSelect(
                '*', 
                DB::raw('odb_txname(name, level, parent_id) as fullname')
            );
        }
    public function measurements()
    {
        return $this->morphMany(Measurement::class, 'measured');
    }

        public function setFullnameAttribute($value) {
            // Full names have only the first letter capitalized
            $value = ucfirst(strtolower($value));

                if ($this->level <= 200) 
                        $this->name = $value;
                if ($this->level >= 210) // sp. or below, strips evertyhing before the last space
                        $this->name = trim(substr($value, strrpos($value, ' ') - strlen($value)));
        }
        // returns rank numbers from common abbreviations
        static public function getRank($rank) {
                switch($rank) {
                case 'div.':
                        return 30;
                case 'cl.':
                        return 60;
                case 'subcl.':
                        return 70;
                case 'ord.':
                        return 90;
                case 'fam.':
                        return 120;
                case 'subfam.':
                        return 130;
                case 'tr.':
                        return 150;
                case 'gen.':
                        return 180;
                case 'subg.':
                        return 190;
                case 'sp.':
                        return 210;
                case 'spec.':
                        return 210;
                case 'subsp.':
                        return 220;
                case 'var.':
                        return 240;
                case 'f.':
                        return 270;
                default:
                        return 0;
                }
        }
        public function author_person() {
                return $this->belongsTo('App\Person', 'author_id');
        }
        public function reference() {
                return $this->belongsTo('App\BibReference', 'bibreference_id');
        }
        public function senior() {
                return $this->belongsTo('App\Taxon', 'senior_id');
        }
        public function juniors() {
                return $this->hasMany('App\Taxon', 'senior_id');
        }
        // For specialists
        public function persons() {
                return $this->belongsToMany('App\Person');
        }

        public function scopeValid($query) {
            return $query->where('valid', '=', 1);
        }

        public function scopeLeaf($query) {
            // partially reimplemented from Etrepat/Baum
              $grammar = $this->getConnection()->getQueryGrammar();
              $rgtCol = $grammar->wrap($this->getQualifiedRightColumnName());
              $lftCol = $grammar->wrap($this->getQualifiedLeftColumnName());
              return $query->where('level', '>=', 210)->whereRaw($rgtCol . ' - ' . $lftCol . ' = 1');
        }

        // Functions for handling API keys
    public function setapikey($name, $reference) {
        $refs = $this->externalrefs()->where('name', $name);
        if ($reference) { // if reference is set...
            if ($refs->count()) { // and we have one already, update it
                $refs->update([ 'reference' => $reference ]);
            } else { // none already, so create one
                $this->externalrefs()->create([
                    'name' => $name,
                    'reference' => $reference,
                ]);
            }
        } else { // no reference set
            if ($refs->count()) // if there was one, delete it
                $refs->delete();
        }
    }
        public function externalrefs() {
                return $this->hasMany('App\TaxonExternal', 'taxon_id');
        }
        public function getMobotAttribute() {
                $ref = $this->externalrefs()->where('name', 'Mobot');
                if ($ref->count())
                        return $ref->first()->reference;
        }
        public function getIpniAttribute() {
                $ref = $this->externalrefs()->where('name', 'IPNI');
                if ($ref->count())
                        return $ref->first()->reference;
        }
        public function getMycobankAttribute() {
                $ref = $this->externalrefs()->where('name', 'Mycobank');
                if ($ref->count())
                        return $ref->first()->reference;
        }
        public function identifications() {
            return $this->hasMany(Identification::class);
        }
        // returns: mixed. May be string if not found in DB, or int (id) if found, or null if no query possible
        static public function getParent($name, $rank, $family) {
            switch (true) {
            case $rank <= 120:
                return null; // Family or above, MOBOT has no parent information
            case $rank > 120 and $rank <= 180: // sub-family to genus, parent should be a family
                if (is_null($family)) return null;
                $searchstr = $family;
                break;
            case $rank > 180 and $rank <= 210: // species, parent should be genus
                $searchstr = substr($name, 0, strpos($name, " "));
                break;
            case $rank > 210 and $rank <= 280: // subsp, var or form, parent should be a species
                preg_match('/^(\w+\s\w+)\s/', $name, $match);
                $searchstr = trim($match[1]);
                break;
            default:
                return null;
            }
            $toparent = Taxon::whereRaw('odb_txname(name, level, parent_id) = ?', [$searchstr]);
            if ($toparent->count()) {
                return [$toparent->first()->id, $searchstr];
            } else {
                return $searchstr;
            }
        }
}
