<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests\Unit;

use Tests\TestCase;
use Tests\RevisionableClass;
use Tests\RevisionableRelationClass;
use DB;
use Auth;
use App\User;
use Lang;

class RevisionableTest extends TestCase
{
    /** Used to clean up database after tests (successfull or not!) */
    public function tearDown()
    {
        \App\Revision::where('revisionable_type', 'Tests\RevisionableClass')->delete();
        DB::unprepared('DELETE FROM revisionable_revisionable_relation');
        RevisionableClass::query()->delete();
        RevisionableRelationClass::query()->delete();
    }

    public function testSimple()
    {
        // creates a simple model, updates it, and tries to recover basic version history
        $model = RevisionableClass::create(['field_1' => 'Blabla']);
        $model->update(['field_1' => 'Lorem ipsum', 'field_2' => 'Ipsum lorem']);

        $model = $model->fresh();
        $this->assertEquals($model->revisionHistory->count(), 3); // 1 for create, plus 2 for the 2 updated fields
        $this->assertEquals($model->revisionHistory[0]->fieldName(), 'created_at');
        $this->assertEquals($model->created_at, $model->revisionHistory[0]->newValue());
        $this->assertNull($model->revisionHistory[0]->userResponsible()); // no logged in user, returns NULL
        $this->assertEquals($model->revisionHistory[1]->fieldName(), 'field_1');
        $this->assertEquals($model->revisionHistory[1]->oldValue(), 'Blabla');
        $this->assertEquals($model->revisionHistory[1]->newValue(), 'Lorem ipsum');
        $this->assertEquals($model->revisionHistory[2]->fieldName(), 'field_2');
//        $this->assertEquals($model->revisionHistory[2]->oldValue(), ''); // -blank- will be tested on other test
        $this->assertEquals($model->revisionHistory[2]->newValue(), 'Ipsum lorem');
    }

    public function testUser()
    {
        // Tests the userResponsible function
        Auth::loginUsingId(1);
        $email = User::find(1)->email;
        // creates a simple model, updates it, and tries to recover basic version history
        $model = RevisionableClass::create();
        $model->update(['field_1' => 'Lorem ipsum']);
        $model = $model->fresh();
        $this->assertEquals($model->revisionHistory[0]->userResponsible()->email, $email);
        $this->assertEquals($model->revisionHistory[1]->userResponsible()->email, $email);
    }

    public function testBelongsToRelation()
    {
        // Sets up a belongsTo relation, updates it, and recovers info using "identifiableName()"
        $r1 = RevisionableRelationClass::create(['field_1' => 'Before']);
        $this->assertEquals($r1->identifiableName(), 'Before');

        $r2 = RevisionableRelationClass::create(['field_1' => 'After']);
        $model = RevisionableClass::create(['revisionable_relation_id' => $r1->id]);
        $model->relationOne()->associate($r2);
        $model->save();

        $model = $model->fresh();
        $this->assertEquals($model->revisionHistory->count(), 2); // 1 for create, 1 for update
        $this->assertEquals($model->revisionHistory[1]->fieldName(), 'revisionable_relation');
        $this->assertEquals($model->revisionHistory[1]->oldValue(), 'Before');
        $this->assertEquals($model->revisionHistory[1]->newValue(), 'After');
    }

    public function testBelongsToManyRelation()
    {
        // Sets up a belongsToMany relation, updates it, and recovers info using "identifiableName()"
        $r1 = RevisionableRelationClass::create(['field_1' => 'First']);
        $r2 = RevisionableRelationClass::create(['field_1' => 'Second']);
        $model = RevisionableClass::create();
        $model->relationTwo()->sync([$r1->id, $r2->id]);

        $model = $model->fresh();
        $this->assertEquals($model->revisionHistory->count(), 3); // 1 for create, 1 for each pivot synced
        $this->assertEquals($model->revisionHistory[1]->fieldName(), 'relationTwo'); // For pivot relations, this is stored as the relation name
        $this->assertEquals($model->revisionHistory[1]->newValue(), 'First');
        $this->assertEquals($model->revisionHistory[2]->newValue(), 'Second');
    }

    public function testDBRaw()
    {
        // Related to https://github.com/VentureCraft/revisionable/issues/293
        $model = RevisionableClass::create();
        $model->update(['field_1' => DB::raw("CONCAT('la','lala')")]);
        $this->assertEquals($model->revisionHistory->count(), 2); // 1 for create, 1 for update
        $this->assertEquals($model->revisionHistory[1]->fieldName(), 'field_1');
        $this->assertEquals($model->revisionHistory[1]->newValue(), 'lalala'); /// The result of DB::raw
    }

    public function testRevisionNullString()
    {
        $r1 = RevisionableRelationClass::create(['field_1' => 'Something']);
        $model = RevisionableClass::create();
        $model->relationOne()->associate($r1);
        $model->save();
        $model = $model->fresh();

        $this->assertEquals($model->revisionHistory[1]->oldValue(), Lang::get('messages.revisionable_nothing'));
    }

    public function testRevisionUnknownString()
    {
        $r2 = RevisionableRelationClass::create(['field_1' => 'To be deleted']);
        $model = RevisionableClass::create();
        $model->relationOne()->associate($r2);
        $model->save();
        $model->relationOne()->associate(null);
        $model->save();
        $r2->delete();

        $this->assertEquals($model->revisionHistory[1]->newValue(), Lang::get('messages.revisionable_unknown'));
    }
}
