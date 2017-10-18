<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTraitObjectsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('trait_objects', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('trait_id')->unsigned();
            $table->foreign('trait_id')->references('id')->on('traits');
            $table->string('object_type');
            $table->unique(['object_type', 'trait_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('trait_objects');
    }
}
