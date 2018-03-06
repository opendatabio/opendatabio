<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAffectedFieldsToUserJobs extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user_jobs', function (Blueprint $table) {
            $table->longText('affected_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('user_jobs', function (Blueprint $table) {
            $table->dropColumn('affected_ids');
        });
    }
}
