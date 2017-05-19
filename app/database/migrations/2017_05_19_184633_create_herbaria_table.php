<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHerbariaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('herbaria', function (Blueprint $table) {
            $table->increments('id');
	    $table->string('name');
	    $table->string('acronym');
	    $table->integer('irn')->unique();
            $table->timestamps();
	});

	DB::table('herbaria')->insert([
		'acronym' => 'INPA',
		'name' => 'Instituto Nacional de Pesquisas da Amazônia',
		'irn' => 124921
	]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('herbaria');
    }
}
