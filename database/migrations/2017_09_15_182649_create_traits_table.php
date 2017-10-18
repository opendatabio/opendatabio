<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTraitsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('traits', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('type');
            $table->string('export_name')->unique();
            $table->string('link_type')->nullable();
            $table->string('unit')->nullable();
            $table->float('range_min')->nullable();
            $table->float('range_max')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('traits');
    }
}
