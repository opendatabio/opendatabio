<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompositeIndexToCollectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collectors', function (Blueprint $table) {
          $table->index(['object_id', 'object_type'], 'collectors_object_id_object_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collectors', function (Blueprint $table) {
          $table->dropIndex('collectors_object_id_object_type');
        });
    }
}
