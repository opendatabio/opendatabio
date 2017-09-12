<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatasetTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dataset_tag', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
	    $table->integer('dataset_id')->unsigned();
	    $table->foreign('dataset_id')->references('id')->on('datasets');
	    $table->integer('tag_id')->unsigned();
	    $table->foreign('tag_id')->references('id')->on('tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dataset_tag');
    }
}
