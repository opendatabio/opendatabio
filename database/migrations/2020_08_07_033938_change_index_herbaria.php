<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeIndexHerbaria extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('herbaria', function (Blueprint $table) {
          $table->dropUnique('herbaria_irn_unique');
          $table->unique(['acronym', 'irn'],'herbaria_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->dropUnique('herbaria_unique');
        $table->unique('irn','herbaria_irn_unique');

    }
}
