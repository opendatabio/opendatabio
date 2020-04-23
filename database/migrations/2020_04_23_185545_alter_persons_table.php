<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPersonsTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
      DB::statement('ALTER TABLE persons ADD notes text NULL AFTER institution');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      DB::statement('ALTER TABLE persons DROP COLUMN notes');
    }
}
