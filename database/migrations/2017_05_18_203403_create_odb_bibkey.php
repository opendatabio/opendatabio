<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOdbBibkey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	DB::unprepared("CREATE FUNCTION odb_bibkey(original TEXT)
	    		RETURNS VARCHAR(191) DETERMINISTIC
			RETURN trim(substr(original,instr(original, '{')+1, instr(original,',')-instr(original,'{')-1));
			");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    DB::unprepared("DROP FUNCTION IF EXISTS odb_bibkey");
        //
    }
}
