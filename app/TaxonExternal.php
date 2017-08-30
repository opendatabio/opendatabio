<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Taxon;

class TaxonExternal extends Model
{
        protected $table = 'taxon_external';
        protected $fillable = ['name', 'taxon_id', 'reference'];
        public function taxon() {
            return $this->belongsTo(Taxon::class);
        }
    //
}
