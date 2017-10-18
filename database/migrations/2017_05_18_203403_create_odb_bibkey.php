<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Migrations\Migration;

class CreateOdbBibkey extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS odb_bibkey');
        DB::unprepared("CREATE FUNCTION odb_bibkey(original TEXT)
	    		RETURNS VARCHAR(191) DETERMINISTIC
			RETURN trim(substr(original,instr(original, '{')+1, instr(original,',')-instr(original,'{')-1));
			");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS odb_bibkey');
    }
}
