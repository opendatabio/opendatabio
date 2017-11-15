<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRevisionableRevisionableRelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revisionable_revisionable_relation', function (Blueprint $table) {
            $table->timestamps();
            $table->integer('revisionable_id')->unsigned();
            $table->foreign('revisionable_id')->references('id')->on('revisionable_test');
            $table->integer('revisionable_relation_id')->unsigned();
            $table->foreign('revisionable_relation_id', 'long_key_name')->references('id')->on('revisionable_relation_test');
            $table->primary(['revisionable_id', 'revisionable_relation_id'], 'really_long_key_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revisionable_revisionable_relation');
    }
}
