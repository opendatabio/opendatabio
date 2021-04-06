<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_tag', function (Blueprint $table) {
          $table->increments('id');
          $table->bigInteger('media_id')->unsigned();
          $table->integer('tag_id')->unsigned();
          $table->foreign('media_id')->references('id')->on('media');
          $table->foreign('tag_id')->references('id')->on('tags');
          $table->unique(['tag_id', 'media_id']);
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
        Schema::dropIfExists('media_tag');
    }
}
