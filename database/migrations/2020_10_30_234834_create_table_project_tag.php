<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableProjectTag extends Migration
{
  /**
   * Run the migrations.
   */
  public function up()
  {
      Schema::create('project_tag', function (Blueprint $table) {
          $table->increments('id');
          $table->timestamps();
          $table->integer('project_id')->unsigned();
          $table->foreign('project_id')->references('id')->on('projects');
          $table->integer('tag_id')->unsigned();
          $table->foreign('tag_id')->references('id')->on('tags');
          $table->unique(['tag_id', 'project_id']);
      });
  }

  /**
   * Reverse the migrations.
   */
  public function down()
  {
      Schema::dropIfExists('project_tag');
  }
}
