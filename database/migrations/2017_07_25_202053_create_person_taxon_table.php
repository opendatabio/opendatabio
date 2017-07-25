<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonTaxonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('person_taxon', function (Blueprint $table) {
            $table->timestamps();
	    $table->integer('person_id')->unsigned();
	    $table->foreign('person_id')->references('id')->on('persons');
	    $table->integer('taxon_id')->unsigned();
	    $table->foreign('taxon_id')->references('id')->on('taxons');
        $table->primary(['person_id', 'taxon_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('person_taxon');
    }
}
