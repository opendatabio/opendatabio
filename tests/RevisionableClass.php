<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests;

use App\Revisionable;
use Illuminate\Database\Eloquent\Model;

class RevisionableClass extends Model
{
    use Revisionable;

    protected $revisionCreationsEnabled = true;

    protected $table = 'revisionable_test';
    protected $fillable = ['field_1', 'field_2', 'revisionable_relation_id'];

    // for revisionable
    public $relatedModels = ['revisionable_relation_id' => 'relationOne'];

    public function relationOne()
    {
        return $this->belongsTo('Tests\RevisionableRelationClass', 'revisionable_relation_id');
    }

    public function relationTwo()
    {
        return $this->belongsToMany('Tests\RevisionableRelationClass', 'revisionable_revisionable_relation', 'revisionable_id', 'revisionable_relation_id');
    }
}
