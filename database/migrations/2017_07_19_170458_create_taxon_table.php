<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxonTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('taxons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('level');
            $table->boolean('valid')->default(0);
            $table->text('notes')->nullable();
            $table->integer('senior_id')->unsigned()->nullable();
            $table->foreign('senior_id')->references('id')->on('taxons');

            // Authorship and references
            $table->string('author')->nullable();
            $table->integer('author_id')->unsigned()->nullable();
            $table->foreign('author_id')->references('id')->on('persons');
            $table->string('bibreference')->nullable();
            $table->integer('bibreference_id')->unsigned()->nullable();
            $table->foreign('bibreference_id')->references('id')->on('bib_references');

            // Node-related columns
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('id')->on('taxons');
            $table->integer('lft')->unsigned()->default(0);
            $table->integer('rgt')->unsigned()->default(0);
            $table->integer('depth')->nullable();

            $table->unique(['name', 'parent_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('taxons');
    }
}
