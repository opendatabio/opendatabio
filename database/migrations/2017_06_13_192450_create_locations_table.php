<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

//use Kalnoy\Nestedset\NestedSet;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
		$table->increments('id');
		$table->string('name');
		// MIGRATION EDITED ON 0.1.0-alpha5 TO DROP NESTEDSET DEPENDENCY
//		NestedSet::columns($table);
		$table->integer('parent_id')->unsigned()->nullable();
		$table->integer('lft')->unsigned()->default(0);
		$table->integer('rgt')->unsigned()->default(0);
		$table->integer('depth')->nullable();
		$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
