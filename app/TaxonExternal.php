<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxonExternal extends Model
{
    protected $table = 'taxon_external';
    protected $fillable = ['name', 'taxon_id', 'reference'];

    public function taxon()
    {
        return $this->belongsTo(Taxon::class);
    }
}
