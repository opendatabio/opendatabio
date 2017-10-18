<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProgressToJobs extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user_jobs', function (Blueprint $table) {
            $table->integer('progress_max')->default(0);
            $table->integer('progress')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('user_jobs', function (Blueprint $table) {
            $table->dropColumn('progress_max');
            $table->dropColumn('progress');
        });
    }
}
