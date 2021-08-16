<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReplaceProjectByDataset extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::disableForeignKeyConstraints();
      Schema::table('individuals', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->renameColumn('project_id', 'dataset_id');
      });
      Schema::table('individuals', function (Blueprint $table) {
        $table->integer("dataset_id")->unsigned()->nullable()->change();
        $table->foreign('dataset_id')->nullable()->references('id')->on('datasets');
      });

      Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->renameColumn('project_id', 'dataset_id');
      });
      Schema::table('vouchers', function (Blueprint $table) {
            $table->integer("dataset_id")->unsigned()->nullable()->change();
            $table->foreign('dataset_id')->nullable()->references('id')->on('datasets');
      });
      Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->renameColumn('project_id', 'dataset_id');
      });
      Schema::table('media', function (Blueprint $table) {
            $table->integer("dataset_id")->unsigned()->nullable()->change();
            $table->foreign('dataset_id')->nullable()->references('id')->on('datasets');
      });
      Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::disableForeignKeyConstraints();
      Schema::table('individuals', function (Blueprint $table) {
            $table->dropForeign(['dataset_id']);
            $table->renameColumn('dataset_id', 'project_id');
      });
      Schema::table('individuals', function (Blueprint $table) {
            $table->integer("project_id")->unsigned()->nullable()->change();
            $table->foreign('project_id')->nullable()->references('id')->on('projects');
      });

      Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['dataset_id']);
            $table->renameColumn('dataset_id', 'project_id');
      });
      Schema::table('vouchers', function (Blueprint $table) {
            $table->integer("project_id")->unsigned()->nullable()->change();
            $table->foreign('project_id')->nullable()->references('id')->on('projects');
      });
      Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['dataset_id']);
            $table->renameColumn('dataset_id', 'project_id');
      });
      Schema::table('media', function (Blueprint $table) {
            $table->integer("project_id")->unsigned()->nullable()->change();
            $table->foreign('project_id')->nullable()->references('id')->on('projects');
      });
      Schema::enableForeignKeyConstraints();
    }
}
