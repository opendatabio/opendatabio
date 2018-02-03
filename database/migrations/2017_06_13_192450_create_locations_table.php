<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            // MIGRATION EDITED ON 0.2.0-alpha1 TO DROP NESTEDSET DEPENDENCY
            //		NestedSet::columns($table);
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('lft')->unsigned()->default(0);
            $table->integer('rgt')->unsigned()->default(0);
            $table->integer('depth')->nullable();
            $table->integer('altitude')->nullable();
            $table->integer('adm_level');
            $table->string('datum')->nullable();
            $table->integer('uc_id')->unsigned()->nullable();
            $table->foreign('uc_id')->references('id')->on('locations');
            $table->unique(['name', 'adm_level']);
            $table->text('notes')->nullable();
            $table->decimal('x')->nullable();
            $table->decimal('y')->nullable();
            $table->decimal('startx')->nullable();
            $table->decimal('starty')->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE locations ADD geom GEOMETRYCOLLECTION');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
