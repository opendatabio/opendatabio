<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests;

use Illuminate\Database\Eloquent\Model;

class RevisionableRelationClass extends Model
{
    protected $table = 'revisionable_relation_test';
    protected $fillable = ['field_1'];

    public function identifiableName()
    {
        return $this->field_1;
    }
}
