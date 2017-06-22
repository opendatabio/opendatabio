<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGeometryToLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
#		$table->multiPolygon('geom');
		$table->integer('altitude')->nullable();
		$table->integer('adm_level');
		$table->string('datum')->nullable();
		$table->integer('uc_id')->unsigned()->nullable();
		$table->foreign('uc_id')->references('id')->on('locations');
		$table->unique(['name', 'adm_level']);
        });
	DB::statement('ALTER TABLE locations ADD geom GEOMETRYCOLLECTION' );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
		$table->dropForeign(['uc_id']);
		$table->dropColumn(['geom', 'altitude', 'adm_level', 'datum', 'uc_id']);
            //
        });
    }
}
