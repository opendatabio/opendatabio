<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Migrations\Migration;

class ChangeDatatypeOfLocationsGeom extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE locations MODIFY COLUMN geom GEOMETRY');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('ALTER TABLE locations MODIFY COLUMN geom GEOMETRYCOLLECTION');
    }
}
