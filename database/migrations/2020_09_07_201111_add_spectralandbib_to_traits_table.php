<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpectralandbibToTraitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('traits', function (Blueprint $table) {
            //field to store length of spectral data for Validation
            $table->integer('value_length')->nullable();
            //field to add possibility of linking traits with a reference
            $table->integer('bibreference_id')->unsigned()->nullable();
            $table->foreign('bibreference_id')->references('id')->on('bib_references');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('traits', function (Blueprint $table) {
            $table->dropColumn('value_length');
            $table->dropForeign(['bibreference_id']);
            $table->dropColumn('bibreference_id');
        });
    }
}
