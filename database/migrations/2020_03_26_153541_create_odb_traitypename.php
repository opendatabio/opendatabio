<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Migrations\Migration;

class CreateOdbTraitypename extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS odb_traittypename');
        DB::unprepared("
CREATE FUNCTION odb_traittypename( type INT)
    RETURNS VARCHAR(50) DETERMINISTIC
BEGIN
  DECLARE tp_name VARCHAR(50);
  SELECT CASE type WHEN 0 THEN 'QUANT_INTEGER' WHEN 1 THEN 'QUANT_REAL' WHEN 2 THEN 'CATEGORICAL' WHEN 3 THEN 'CATEGORICAL_MULTIPLE' WHEN 4 THEN 'ORDINAL' WHEN 5 THEN 'TEXT' WHEN 6 THEN 'COLOR' WHEN 7 THEN 'LINK' ELSE '' END INTO tp_name;
  RETURN tp_name;
END;
");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS odb_traittypename');
    }
}
