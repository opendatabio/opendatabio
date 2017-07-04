<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddXToLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
		$table->decimal('x')->nullable();
		$table->decimal('y')->nullable();
		$table->decimal('startx')->nullable();
		$table->decimal('starty')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
		$table->dropColumn(['x', 'y', 'startx', 'starty']);
            //
        });
    }
}
