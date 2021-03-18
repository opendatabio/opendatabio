<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
              $table->dropForeign('vouchers_person_id_foreign');
              $table->dropIndex('vouchers_person_id_number_unique');
              $table->dropIndex('parent');
              $table->dropColumn('parent_id');
              $table->dropColumn('parent_type');
              $table->dropColumn('person_id');
              $table->string('number')->nullable()->change();
              $table->date('date')->nullable()->change();
              $table->integer('individual_id')->unsigned()->after('id');
              $table->integer('herbarium_id')->unsigned()->after('individual_id');
              $table->string('herbarium_number')->nullable()->after('herbarium_id');
              $table->boolean('herbarium_type')->default(0)->after('herbarium_number');
              //$table->foreign('herbarium_id')->references('id')->on('herbaria');
              $table->unique(['individual_id','herbarium_id','herbarium_number','number'],'vouchers_unique');

              $table->foreign('individual_id')->references('id')->on('individuals');              
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('vouchers', function (Blueprint $table) {
        $table->dropForeign('vouchers_individual_id_foreign');
        $table->dropForeign('vouchers_herbarium_id_foreign');
        $table->dropIndex('vouchers_unique');
        $table->dropColumn('individual_id');
        $table->dropColumn('herbarium_id');
        $table->dropColumn('herbarium_number');
        $table->dropColumn('herbarium_type');
      });
    }
}
