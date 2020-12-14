<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use App\Taxon;
use App\Dataset;
use App\Summary;
use App\Project;

class CreateCountsTable extends Migration
{
  /**
   * Run the migrations.
   */
  public function up()
  {

      Schema::create('counts', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('object_id');
          $table->string('object_type');
          $table->integer('value');
          $table->string('target');
          $table->integer('scope_id')->nullable();
          $table->string('scope_type');
          $table->timestamps();
          $table->unique(['object_id', 'object_type', 'target','scope_id', 'scope_type']);
      });

      Summary::updateSummaryTable($what="both",null,null);


  }

  /**
   * Reverse the migrations.
   */
  public function down()
  {
      Schema::dropIfExists('counts');
  }

}
