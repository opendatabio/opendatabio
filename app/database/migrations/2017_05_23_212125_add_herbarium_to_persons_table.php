<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHerbariumToPersonsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('persons', function (Blueprint $table) {
			$table->integer('herbarium_id')->unsigned()->nullable();
			$table->foreign('herbarium_id')->references('id')->on('herbaria');
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
		Schema::table('persons', function (Blueprint $table) {
			$table->dropForeign(['herbarium_id']);
			$table->dropColumn('herbarium_id');

		});
	}
}
