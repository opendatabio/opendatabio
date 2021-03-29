<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Taxon;

class AddTaxonBaumKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('taxons', function (Blueprint $table) {
          $table->index(['lft', 'rgt', 'level'], 'taxons_tree');
          $table->index(['level'], 'taxons_level');
      });
      if (!Taxon::isValidNestedSet()) {
          Taxon::rebuild(true);
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('taxons', function (Blueprint $table) {
          $table->dropIndex('taxons_tree');
          $table->dropIndex('taxons_level');
      });
    }
}
