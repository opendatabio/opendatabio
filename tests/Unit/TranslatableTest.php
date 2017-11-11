<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Language;
use App\UserTranslation;
use Tests\TranslatableClass;
use Illuminate\Support\Facades\App;

// ***** NOTICE: this test case presumes there are at least two App\Languages defined! *****
class TranslatableTest extends TestCase
{
    /** Used to clean up database after tests (successfull or not!) */
    public function tearDown()
    {
        UserTranslation::where('translatable_type', 'Tests\TranslatableClass')->delete();
        TranslatableClass::query()->delete();
    }

    /**
     * Some very basic tests for empty translatable models.
     */
    public function testEmpty()
    {
        // Empty keys should return NULL on "translate"
        $tr = new TranslatableClass();
        $lang = Language::first()->id;
        $this->assertNull($tr->translate(UserTranslation::NAME, $lang));
        $this->assertNull($tr->translate(UserTranslation::DESCRIPTION, $lang));
        // With convenience accessors (for display), return a warning
        $this->assertEquals($tr->name, 'Missing translation');
        $this->assertEquals($tr->description, 'Missing translation');
    }

    public function testSimple()
    {
        // Sets the name and translation for a key in two different languages, then retrieves all of them
        $lang = Language::first();
        $lang2 = Language::all()[1];
        $tr = TranslatableClass::create();
        $tr->setTranslation(UserTranslation::NAME, $lang->id, 'Lorem ipsum');
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang->id, 'Ipsum lorem');
        $tr->setTranslation(UserTranslation::NAME, $lang2->id, 'Dolorem ipsum');
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang2->id, 'Ipsum dolorem');

        $tr = $tr->fresh();
        $this->assertEquals($tr->translate(UserTranslation::NAME, $lang->id), 'Lorem ipsum');
        $this->assertEquals($tr->translate(UserTranslation::DESCRIPTION, $lang->id), 'Ipsum lorem');
        $this->assertEquals($tr->translate(UserTranslation::NAME, $lang2->id), 'Dolorem ipsum');
        $this->assertEquals($tr->translate(UserTranslation::DESCRIPTION, $lang2->id), 'Ipsum dolorem');

        // Gets the convenience accessors in the defined Locale
        App::setLocale($lang->code);
        $this->assertEquals($tr->name, 'Lorem ipsum');
        $this->assertEquals($tr->description, 'Ipsum lorem');
        App::setLocale($lang2->code);
        $this->assertEquals($tr->name, 'Dolorem ipsum');
        $this->assertEquals($tr->description, 'Ipsum dolorem');
    }

    public function testFallback()
    {
        // Sets the name and translation for a key in one language, then tests falling back from the other
        $lang = Language::first();
        $lang2 = Language::all()[1];
        $tr = TranslatableClass::create();
        $tr->setTranslation(UserTranslation::NAME, $lang->id, 'Lorem ipsum fallback');
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang->id, 'Ipsum lorem fallback');

        $tr = $tr->fresh();
        App::setLocale($lang2->code);
        $this->assertEquals($tr->name, 'Lorem ipsum fallback');
        $this->assertEquals($tr->description, 'Ipsum lorem fallback');
    }

    public function testMutators()
    {
        // Creates, updates, deletes a key
        $lang = Language::first();
        $lang2 = Language::all()[1];
        $tr = TranslatableClass::create();
        $tr->setTranslation(UserTranslation::NAME, $lang->id, 'PRESERVE');
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang2->id, 'PRESERVE');
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang->id, 'To be changed');

        // just for sanity...
        $tr = $tr->fresh();
        $this->assertEquals($tr->translate(UserTranslation::DESCRIPTION, $lang->id), 'To be changed');

        // updates a single translation
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang->id, 'Updated');
        $tr = $tr->fresh();
        $this->assertEquals($tr->translate(UserTranslation::DESCRIPTION, $lang->id), 'Updated');
        $this->assertEquals($tr->translate(UserTranslation::NAME, $lang->id), 'PRESERVE');
        $this->assertEquals($tr->translate(UserTranslation::DESCRIPTION, $lang2->id), 'PRESERVE');

        // removes a single translation
        $tr->setTranslation(UserTranslation::DESCRIPTION, $lang->id, null);
        $tr = $tr->fresh();
        $this->assertNull($tr->translate(UserTranslation::DESCRIPTION, $lang->id));
        $this->assertEquals($tr->translate(UserTranslation::NAME, $lang->id), 'PRESERVE');
        $this->assertEquals($tr->translate(UserTranslation::DESCRIPTION, $lang2->id), 'PRESERVE');
    }

    public function testEagerLoadLanguage()
    {
        // core functionality of the Translatable trait only work if "Language" is
        // always eager loaded by UserTranslation

        $lang = Language::first();
        $tr = TranslatableClass::create();
        $tr->setTranslation(UserTranslation::NAME, $lang->id, 'Lorem Ipsum');

        $tr = TranslatableClass::with('translations')->first();
        $this->assertTrue($tr->translations[0]->relationLoaded('language'));
    }
}
