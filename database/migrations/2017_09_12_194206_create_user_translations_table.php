<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('translatable_id');
            $table->string('translatable_type');
            $table->integer('language_id')->unsigned();
            $table->foreign('language_id')->references('id')->on('languages');
            $table->tinyInteger('translation_type');
            $table->unique(['language_id', 'translatable_type', 'translatable_id', 'translation_type'], 'user_translations_language_translatable_unique');
            $table->string('translation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('user_translations');
    }
}
