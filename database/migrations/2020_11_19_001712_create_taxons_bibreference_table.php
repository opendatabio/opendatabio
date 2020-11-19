<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxonsBibReferenceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxon_bibreference', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('taxon_id')->unsigned();
          $table->foreign('taxon_id')->references('id')->on('taxons');
          $table->integer('bib_reference_id')->unsigned();
          $table->foreign('bib_reference_id')->references('id')->on('bib_references');
          $table->timestamps();
          $table->unique(['bib_reference_id', 'taxon_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxon_bibreference');
    }
}
