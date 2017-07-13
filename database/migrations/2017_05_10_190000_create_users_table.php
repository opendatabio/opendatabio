<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
	    $table->integer('person_id')->unsigned()->nullable();
	    $table->foreign('person_id')->references('id')->on('persons');
	    $table->integer('access_level')->default(0);
        });

	DB::table('users')->insert([
		'email' => 'admin@example.org',
		'password' => bcrypt('password1'),
		'access_level' => 2,
	]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
