<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Migrations\Migration;

use App\Location;

// See issue #190 on GitHub

class CreateWorldLocation extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $WORLD = Location::create(['name' => 'World', 'adm_level' => -1]);
        Location::where('adm_level', 0)->each(function ($location) use ($WORLD) {$location->makeChildOf($WORLD); });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $WORLD = Location::world();
        if ($WORLD) {
            $WORLD->children()->each(function ($location) {$location->makeRoot(); });
            $WORLD->delete();
        }
    }
}
