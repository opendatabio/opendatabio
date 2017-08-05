<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TaxonExternalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxon_external', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('taxon_id')->unsigned();
            $table->foreign('taxon_id')->references('id')->on('taxons')->onDelete('cascade');
            $table->string('name');
            $table->unique(['name', 'taxon_id']);
            $table->string('reference');
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
        Schema::dropIfExists('taxon_external');
    }
}
