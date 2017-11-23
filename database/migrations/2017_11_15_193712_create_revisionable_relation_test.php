<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRevisionableRelationTest extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('revisionable_relation_test', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('field_1')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('revisionable_relation_test');
    }
}
