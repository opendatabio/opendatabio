<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Baum\Node;
use DB;

class Taxon extends Node
{
        protected $fillable = ['name', 'level', 'valid', 'validreference', 'senior_id', 'author', 'author_id',
                'bibreference', 'bibreference_id'];
        // for use in selects, lists the most common tax levels
        static public function TaxLevels() { 
                return [0, 30, 60, 70, 90, 100, 120, 130, 150, 180, 210, 220, 240, 270]; 
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
    //
}
