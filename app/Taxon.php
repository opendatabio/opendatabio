<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Baum\Node;
use DB;

class Taxon extends Node
{
        protected $fillable = ['name', 'level', 'valid', 'validreference', 'senior_id', 'author', 'author_id',
                'bibreference', 'bibreference_id', 'parent_id'];
        // for use in selects, lists the most common tax levels
        static public function TaxLevels() { 
                return [0, 30, 60, 70, 90, 100, 120, 130, 150, 180, 210, 220, 240, 270]; 
        }
        // returns rank numbers from common abbreviations
        static public function getRank($rank) {
                switch($rank) {
                case 'cl.':
                        return 60;
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
/*        public function externalrefs() {
                return $this->hasMany('App\TaxonExternal', 'taxon_id');
        }
        public function mobot_key() {
            return 0;
        }
 */
    //
}
