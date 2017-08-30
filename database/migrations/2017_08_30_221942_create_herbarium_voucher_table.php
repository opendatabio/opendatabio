<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHerbariumVoucherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('herbarium_voucher', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('herbarium_id')->unsigned()->nullable();
            $table->foreign('herbarium_id')->references('id')->on('herbaria');
            $table->integer('voucher_id')->unsigned()->nullable();
            $table->foreign('voucher_id')->references('id')->on('vouchers');
            $table->unique(['voucher_id', 'herbarium_id']);
            $table->string('number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('herbarium_voucher');
    }
}
