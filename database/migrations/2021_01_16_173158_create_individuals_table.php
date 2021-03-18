<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndividualsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individuals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tag'); //this for either tag or collector number when individual is specimen
            $table->date('date');
            $table->text('notes')->nullable();
            $table->integer('project_id')->unsigned();
            $table->integer('identification_individual_id')->nullable(); //this is for storing the id of the individual that has identification, allowing individuals to have an identification that belongs to another individual. If this is equal to id, then is a self identification
            $table->timestamps();
            $table->foreign('project_id')->references('id')->on('projects');
            $table->index(['tag']);
            $table->index('identification_individual_id');
        });
        //DB::statement('ALTER TABLE individuals ADD relative_position POINT');

        //allows tracking animal positions when individual moves, else will have a single record here
        Schema::create('individual_location', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('location_id')->unsigned();
            $table->integer('individual_id')->unsigned();
            $table->dateTime('date_time')->nullable();
            $table->text('notes')->nullable();
            $table->point('relative_position')->nullable();
            $table->integer('altitude')->nullable(); //allows individual at same location to have altitude values associated (historical data mostly)
            $table->boolean('first')->default(0); //this allows to query the first location for the unique rule and is filled only for the first location of each individual
            $table->timestamps();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('individual_id')->references('id')->on('individuals');
            $table->unique(['individual_id', 'date_time']);
            $table->index(['location_id','first']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('individual_location');
        Schema::dropIfExists('individuals');
    }
}
