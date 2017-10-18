<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        DB::table('tags')->insert([
            'id' => 1,
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '1',
            'translatable_type' => 'App\\Tag',
            'language_id' => '1',
            'translation_type' => '0',
            'translation' => 'Census',
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '1',
            'translatable_type' => 'App\\Tag',
            'language_id' => '2',
            'translation_type' => '0',
            'translation' => 'Censo',
        ]);

        DB::table('tags')->insert([
            'id' => 2,
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '2',
            'translatable_type' => 'App\\Tag',
            'language_id' => '1',
            'translation_type' => '0',
            'translation' => 'Floristics',
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '2',
            'translatable_type' => 'App\\Tag',
            'language_id' => '2',
            'translation_type' => '0',
            'translation' => 'Florística',
        ]);

        DB::table('tags')->insert([
            'id' => 3,
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '3',
            'translatable_type' => 'App\\Tag',
            'language_id' => '1',
            'translation_type' => '0',
            'translation' => 'Functional characters',
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '3',
            'translatable_type' => 'App\\Tag',
            'language_id' => '2',
            'translation_type' => '0',
            'translation' => 'Caracteres funcionais',
        ]);

        DB::table('tags')->insert([
            'id' => 4,
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '4',
            'translatable_type' => 'App\\Tag',
            'language_id' => '1',
            'translation_type' => '0',
            'translation' => 'Mollecular data',
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '4',
            'translatable_type' => 'App\\Tag',
            'language_id' => '2',
            'translation_type' => '0',
            'translation' => 'Dados moleculares',
        ]);

        DB::table('tags')->insert([
            'id' => 5,
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '5',
            'translatable_type' => 'App\\Tag',
            'language_id' => '1',
            'translation_type' => '0',
            'translation' => 'Soil chemistry',
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '5',
            'translatable_type' => 'App\\Tag',
            'language_id' => '2',
            'translation_type' => '0',
            'translation' => 'Química do solo',
        ]);

        DB::table('tags')->insert([
            'id' => 6,
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '6',
            'translatable_type' => 'App\\Tag',
            'language_id' => '1',
            'translation_type' => '0',
            'translation' => 'Taxonomic and morphologic characters',
        ]);
        DB::table('user_translations')->insert([
            'translatable_id' => '6',
            'translatable_type' => 'App\\Tag',
            'language_id' => '2',
            'translation_type' => '0',
            'translation' => 'Caracteres taxonômicos e morfológicos',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('tags');
    }
}
