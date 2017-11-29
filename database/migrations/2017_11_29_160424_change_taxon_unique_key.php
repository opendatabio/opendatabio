<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTaxonUniqueKey extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('taxons', function (Blueprint $table) {
            $table->unique(['name', 'parent_id', 'author', 'author_id'], 'taxons_name_parent_author_unique');
            $table->dropUnique('taxons_name_parent_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('taxons', function (Blueprint $table) {
            $table->unique(['name', 'parent_id']);
            $table->dropUnique('taxons_name_parent_author_unique');
        });
    }
}
