<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationTableSpatialIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        /* give polygon to world because spatial index does not permit null values */
        DB::statement('Update locations SET geom=ST_GeomFromText("POLYGON((-180 -90, -180 90, 180 90, 180 -90, -180 -90))") WHERE id=1;');
        DB::statement("ALTER TABLE locations CHANGE geom geom GEOMETRY NOT NULL;");
        DB::statement("CREATE SPATIAL INDEX spatial_index ON locations (geom);");
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        /* give polygon to world because spatial index does not permit null values */
        DB::statement('Update locations SET geom=null WHERE id=1;');
        DB::statement("ALTER TABLE locations CHANGE geom geom GEOMETRY NULL;");
        DB::statement("DROP INDEX spatial_index ON locations;");
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
