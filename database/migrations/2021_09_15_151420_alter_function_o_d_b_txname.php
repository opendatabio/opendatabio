<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFunctionODBTxname extends Migration
{
      public function up()
      {
          DB::unprepared('DROP FUNCTION IF EXISTS odb_txname');
          DB::unprepared("
  CREATE FUNCTION odb_txname( name VARCHAR(191), level INT, parent_id INT)
      RETURNS VARCHAR(191) DETERMINISTIC
  BEGIN
      DECLARE p_name, p_p_name, part VARCHAR(191);
      DECLARE p_parent_id INT;
      DECLARE p_unpublished INT;
      IF level < 200 THEN
          RETURN name;
      END IF;
      IF level = 210 THEN
          SELECT IF(name LIKE '% %',1,0) INTO p_unpublished;
          IF p_unpublished=0 THEN
            SELECT taxons.name INTO p_name FROM taxons WHERE id = parent_id;
            RETURN CONCAT(p_name, ' ', name);
          ELSE
            RETURN name;
          END IF;
      END IF;

      SELECT taxons.name, taxons.parent_id INTO p_name, p_parent_id FROM taxons WHERE id = parent_id;
      SELECT taxons.name INTO p_p_name FROM taxons WHERE id = p_parent_id;
      SELECT CASE level WHEN 220 THEN 'subsp.' WHEN 240 THEN 'var.' WHEN 270 THEN 'f.' ELSE '' END INTO part;
       RETURN CONCAT_WS(' ', p_p_name, p_name, part, name);
  END;
  ");
      }

      /**
       * Reverse the migrations.
       */
      public function down()
      {
          DB::unprepared('DROP FUNCTION IF EXISTS odb_txname');
      }

}
