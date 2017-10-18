<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('trait_id')->unsigned();
            $table->foreign('trait_id')->references('id')->on('traits');
            $table->integer('measured_id');
            $table->string('measured_type');
            $table->date('date');
            $table->integer('dataset_id')->unsigned();
            $table->foreign('dataset_id')->references('id')->on('datasets');
            $table->integer('person_id')->unsigned()->nullable();
            $table->foreign('person_id')->references('id')->on('persons');
            $table->integer('bibreference_id')->unsigned()->nullable();
            $table->foreign('bibreference_id')->references('id')->on('bib_references');
            $table->float('value')->nullable();
            $table->integer('value_i')->nullable();
            $table->string('value_a')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('measurements');
    }
}
