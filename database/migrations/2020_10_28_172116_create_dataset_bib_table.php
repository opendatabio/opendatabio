<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatasetBibTable extends Migration
{
  /**
   * Run the migrations.
   */
  public function up()
  {
      Schema::create('dataset_bibreference', function (Blueprint $table) {
          $table->increments('id');
          $table->timestamps();
          $table->integer('dataset_id')->unsigned();
          $table->foreign('dataset_id')->references('id')->on('datasets');
          $table->integer('bib_reference_id')->unsigned();
          $table->foreign('bib_reference_id')->references('id')->on('bib_references');
          $table->integer('mandatory')->nullable();
          $table->unique(['bib_reference_id', 'dataset_id']);
      });
  }

  /**
   * Reverse the migrations.
   */
  public function down()
  {
      Schema::dropIfExists('dataset_bibreference');
  }
}
