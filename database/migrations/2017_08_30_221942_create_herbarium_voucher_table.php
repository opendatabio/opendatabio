<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHerbariumVoucherTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('herbarium_voucher', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('herbarium_id')->unsigned();
            $table->foreign('herbarium_id')->references('id')->on('herbaria');
            $table->integer('voucher_id')->unsigned();
            $table->foreign('voucher_id')->references('id')->on('vouchers');
            $table->unique(['voucher_id', 'herbarium_id']);
            $table->string('herbarium_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('herbarium_voucher');
    }
}
