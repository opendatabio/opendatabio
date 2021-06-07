<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserJobTable extends Migration
{
    /**
     * Run the migrations.
     * THIS IS CHANGE IS REQUIRED TO USE A REDIS QUEUE OPTION
     *
     * @return void
     */
    public function up()
    {

      Schema::disableForeignKeyConstraints();
      Schema::table('user_jobs', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
        });
      Schema::enableForeignKeyConstraints();
      Schema::table('user_jobs', function (Blueprint $table) {
            $table->string('job_id')->change();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('user_jobs', function (Blueprint $table) {
        $table->integer('job_id')->unsigned()->nullable()->change(); // Laravel queue job id
        $table->foreign('job_id')->references('id')->on('jobs')
        ->onDelete('set null');
      });
    }
}
