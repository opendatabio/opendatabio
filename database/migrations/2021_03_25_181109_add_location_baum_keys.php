<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Location;

class AddLocationBaumKeys extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('locations', function (Blueprint $table) {
        $table->index(['lft', 'rgt', 'adm_level'], 'locations_tree');
        $table->index(['adm_level'], 'locations_level');
    });
    if (!Location::isValidNestedSet()) {
        Location::rebuild(true);
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('locations', function (Blueprint $table) {
        $table->dropIndex('locations_tree');
        $table->dropIndex('locations_level');
    });
  }
}
