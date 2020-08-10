<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Herbarium extends Model
{

    const NotType =0;
    const Type = 1;
    const Holotype = 2;
    const Isotype = 3;
    const Paratype = 4;
    const Lectotype = 5;
    const Isolectotype = 6;
    const Syntype = 7;
    const Isosyntype = 8;
    const Neotype = 9;
    const Epitype = 10;
    const Isoepitype = 11;
    const Cultivartype = 12;
    const Clonotype = 13;
    const Topotype = 14;
    const Phototype = 15;
    const NOMENCLATURE_TYPE = [
      self::NotType,
      self::Type,
      self::Holotype,
      self::Isotype,
      self::Paratype,
      self::Lectotype,
      self::Isolectotype,
      self::Syntype,
      self::Isosyntype,
      self::Neotype,
      self::Epitype,
      self::Isoepitype,
      self::Cultivartype,
      self::Clonotype,
      self::Topotype,
      self::Phototype,
    ];

    protected $fillable = ['name', 'acronym', 'irn'];

    public function rawLink()
    {
        return "<a href='".url('herbaria/'.$this->id)."'>".htmlspecialchars($this->acronym).'</a>';
    }

    public function persons()
    {
        return $this->hasMany(Person::class);
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class)->withPivot('herbarium_number');
    }

    // For Revisionable
    public function identifiableName()
    {
        return $this->acronym;
    }
}
