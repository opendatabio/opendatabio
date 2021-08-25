<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPictureTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('pictures');
        Schema::dropIfExists('picture_tag');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::create('pictures', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('object_id')->unsigned();
          $table->string('object_type');
          $table->string('license')->nullable();
          $table->text('notes')->nullable();
          $table->text('metadata')->nullable();
          $table->timestamps();
      });
      Schema::create('picture_tag', function (Blueprint $table) {
          $table->increments('id');
          $table->timestamps();
          $table->integer('picture_id')->unsigned();
          $table->foreign('picture_id')->references('id')->on('pictures');
          $table->integer('tag_id')->unsigned();
          $table->foreign('tag_id')->references('id')->on('tags');
          $table->unique(['tag_id', 'picture_id']);
      });

    }
}
