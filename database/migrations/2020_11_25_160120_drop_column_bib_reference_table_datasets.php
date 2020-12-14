<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnBibReferenceTableDatasets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('datasets', function (Blueprint $table) {
          $table->dropForeign('datasets_bibreference_id_foreign');
          $table->dropColumn('bibreference_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('datasets', function (Blueprint $table) {
          $table->integer('bibreference_id')->unsigned()->nullable();
          $table->foreign('bibreference_id')->references('id')->on('bib_references');
        });
    }
}
