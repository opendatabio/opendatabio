<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTraitCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('trait_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('trait_id')->unsigned();
            $table->foreign('trait_id')->references('id')->on('traits');
            $table->integer('rank')->nullable();
            $table->unique(['rank', 'trait_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('trait_categories');
    }
}
