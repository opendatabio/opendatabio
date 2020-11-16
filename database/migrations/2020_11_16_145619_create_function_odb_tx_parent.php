<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFunctionOdbTxParent extends Migration
{
  /**
   * Run the migrations.
   */
  public function up()
  {
      DB::unprepared('DROP FUNCTION IF EXISTS odb_txparent');
      DB::unprepared("
CREATE FUNCTION odb_txparent(lft INT, level INT)
  RETURNS VARCHAR(191) DETERMINISTIC
  BEGIN
      DECLARE p_name VARCHAR(191);
      SELECT odb_txname(taxons.name,taxons.level,taxons.parent_id) INTO p_name FROM taxons WHERE taxons.lft <= lft AND taxons.rgt >= lft AND taxons.level=level;
      RETURN p_name;
  END;
");
  }

  /**
   * Reverse the migrations.
   */
  public function down()
  {
      DB::unprepared('DROP FUNCTION IF EXISTS odb_txparent');
  }
}
