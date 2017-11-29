<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceOnIdentification extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('identifications', function (Blueprint $table) {
            $table->string('herbarium_reference')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('identifications', function (Blueprint $table) {
            $table->dropColumn('herbarium_reference');
        });
    }
}
