<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRevisionableTest extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('revisionable_test', function (Blueprint $table) {
            $table->increments('id');
            $table->string('field_1')->nullable();
            $table->string('field_2')->nullable();
            $table->integer('revisionable_relation_id')->unsigned()->nullable();
            $table->foreign('revisionable_relation_id')->references('id')->on('revisionable_relation_test');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('revisionable_test');
    }
}
