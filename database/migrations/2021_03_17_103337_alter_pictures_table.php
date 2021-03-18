<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPicturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('pictures', function (Blueprint $table) {
        $table->string('license')->nullable()->after('object_type');
        $table->text('notes')->nullable()->after('license');
        $table->text('metadata')->nullable()->after('notes');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('pictures', function (Blueprint $table) {
        $table->dropColumn('license');
        $table->dropColumn('notes');
        $table->dropColumn('metadata');
      });
    }
}
