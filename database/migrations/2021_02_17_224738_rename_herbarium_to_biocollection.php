<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameHerbariumToBiocollection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('herbaria', 'biocollections');
        Schema::table('biocollections', function (Blueprint $table) {
          $table->dropIndex('herbaria_unique');
          $table->unique(['acronym','id'],'biocollection_unique');
        });


        Schema::table('identifications', function (Blueprint $table) {
          $table->dropForeign('identifications_herbarium_id_foreign');
          $table->renameColumn('herbarium_id', 'biocollection_id');
          $table->renameColumn('herbarium_reference', 'biocollection_reference');
          $table->foreign('biocollection_id')->references('id')->on('biocollections');
        });
        Schema::table('persons', function (Blueprint $table) {
          $table->dropForeign('persons_herbarium_id_foreign');
          $table->renameColumn('herbarium_id', 'biocollection_id');
          $table->foreign('biocollection_id')->references('id')->on('biocollections');

        });
        Schema::disableForeignKeyConstraints();
        Schema::table('vouchers', function (Blueprint $table) {
          //$table->dropUnique('vouchers_unique');
          $table->renameColumn('herbarium_id', 'biocollection_id');
          $table->renameColumn('herbarium_number', 'biocollection_number');
          $table->renameColumn('herbarium_type', 'biocollection_type');
          $table->foreign('biocollection_id')->references('id')->on('biocollections');
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

      Schema::rename('biocollections', 'herbaria');
      Schema::table('herbaria', function (Blueprint $table) {
        $table->dropIndex('biocollection_unique');
        $table->unique(['acronym','id']);
      });
      Schema::table('identifications', function (Blueprint $table) {
        $table->dropForeign('identifications_biocollection_id_foreign');
        $table->renameColumn( 'biocollection_id','herbarium_id');
        $table->renameColumn('biocollection_reference','herbarium_reference', );
        $table->foreign('herbarium_id')->references('id')->on('herbaria');

      });
      Schema::table('persons', function (Blueprint $table) {
        $table->dropForeign('persons_biocollection_id_foreign');
        $table->renameColumn( 'biocollection_id','herbarium_id');
        $table->foreign('herbarium_id')->references('id')->on('herbaria');
      });
      Schema::table('vouchers', function (Blueprint $table) {
        $table->dropForeign(['biocollection_id']);
        $table->renameColumn('biocollection_id','herbarium_id');
        $table->renameColumn('biocollection_number','herbarium_number');
        $table->renameColumn('biocollection_type','herbarium_type');
        $table->foreign('herbarium_id')->references('id')->on('biocollections');
        $table->renameIndex('vouchers_biocollection_id_foreign','vouchers_herbarium_id_foreign');
      });
    }
}
