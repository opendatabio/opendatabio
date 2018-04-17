<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormTraitsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('form_traits', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('form_id')->unsigned();
            $table->foreign('form_id')->references('id')->on('forms');
            $table->integer('trait_id')->unsigned();
            $table->foreign('trait_id')->references('id')->on('traits');
            $table->unique(['form_id', 'trait_id']);
            $table->integer('order');
            $table->unique(['form_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('form_traits');
    }
}
