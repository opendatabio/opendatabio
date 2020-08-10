<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHerbariumTypeToHerbariumVoucherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('herbarium_voucher', function (Blueprint $table) {
            //
            $table->integer('herbarium_type');
            $table->string('herbarium_number')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('herbarium_voucher', function (Blueprint $table) {
            //
            $table->dropColumn('herbarium_type');
            $table->string('herbarium_number')->nullable(false)->change();
        });
    }
}
