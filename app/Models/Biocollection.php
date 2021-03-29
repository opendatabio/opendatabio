<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biocollection extends Model
{

    const NOT_TYPE =0;
    const GENERIC_TYPE = 1;
    const HOLOTYPE = 2;
    const ISOTYPE = 3;
    const PARATYPE = 4;
    const LECTOTYPE = 5;
    const ISOLECTOTYPE = 6;
    const SYNTYPE = 7;
    const ISOSYNTYPE = 8;
    const NEOTYPE = 9;
    const EPITYPE = 10;
    const ISOEPITYPE = 11;
    const CULTIVARTYPE = 12;
    const CLONOTYPE = 13;
    const TOPOTYPE = 14;
    const PHOTOTYPE = 15;
    const NOMENCLATURE_TYPE = [
      self::NOT_TYPE,
      self::GENERIC_TYPE,
      self::HOLOTYPE,
      self::ISOTYPE,
      self::PARATYPE,
      self::LECTOTYPE,
      self::ISOLECTOTYPE,
      self::SYNTYPE,
      self::ISOSYNTYPE,
      self::NEOTYPE,
      self::EPITYPE,
      self::ISOEPITYPE,
      self::CULTIVARTYPE,
      self::CLONOTYPE,
      self::TOPOTYPE,
      self::PHOTOTYPE,
    ];

    protected $table = 'biocollections';

    protected $fillable = ['name', 'acronym', 'irn'];

    public function rawLink()
    {
        return "<a href='".url('biocollections/'.$this->id)."'>".htmlspecialchars($this->acronym).'</a>';
    }

    public function persons()
    {
        return $this->hasMany(Person::class);
    }

    public function vouchers()
    {
        //return $this->belongsToMany(Voucher::class)->withPivot('biocollection_number');
        return $this->hasMany(Voucher::class);
    }

    // For Revisionable
    public function identifiableName()
    {
        return $this->acronym;
    }


}
