<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDatasetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->renameColumn('notes', 'description');
            $table->text('policy')->nullable();
            $table->text('metadata')->nullable();
            //
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
          $table->renameColumn('description', 'notes');
          $table->dropColumn('policy');
          $table->dropColumn('metadata');
            //
        });
    }
}
