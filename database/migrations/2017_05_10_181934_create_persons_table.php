<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
	    $table->string('full_name');
	    $table->string('abbreviation');
	    $table->unique('abbreviation');
	    $table->string('email')->nullable();
	    $table->string('institution')->nullable();
	    $table->integer('herbarium_id')->unsigned()->nullable();
	    $table->foreign('herbarium_id')->references('id')->on('herbaria');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('persons');
    }
}
