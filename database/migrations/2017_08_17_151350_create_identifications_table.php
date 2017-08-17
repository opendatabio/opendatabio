<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIdentificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identifications', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('person_id')->unsigned()->nullable();
            $table->foreign('person_id')->references('id')->on('persons');
            $table->integer('taxon_id')->unsigned();
            $table->foreign('taxon_id')->references('id')->on('taxons');
            $table->integer('object_id')->unsigned();
            $table->string('object_type');
            $table->date('date');
            $table->integer('modifier')->default(0);
            $table->integer('herbarium_id')->unsigned()->nullable();
            $table->foreign('herbarium_id')->references('id')->on('herbaria');
            $table->text('notes')->nullable();
            $table->unique(['object_id', 'object_type']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identifications');
    }
}
