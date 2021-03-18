<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//use DB;

class DropUnusedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('revisionable_revisionable_relation');
        Schema::dropIfExists('revisionable_test');
        Schema::dropIfExists('revisionable_relation_test');
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('herbarium_voucher');
        Schema::dropIfExists('plants');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
