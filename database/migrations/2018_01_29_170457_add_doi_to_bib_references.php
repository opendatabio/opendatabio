<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDoiToBibReferences extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bib_references', function (Blueprint $table) {
            $table->string('doi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bib_references', function (Blueprint $table) {
            $table->dropColumn('doi');
        });
    }
}
