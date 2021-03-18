<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOdbfunctions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      DB::unprepared('DROP FUNCTION IF EXISTS odb_ind_fullname');
      DB::unprepared("CREATE FUNCTION odb_ind_fullname(id INT, tag VARCHAR(191))
        RETURNS VARCHAR(191) DETERMINISTIC
        BEGIN

        DECLARE location,person VARCHAR(191);

        SELECT locations.name into location FROM individual_location JOIN locations ON locations.id=individual_location.location_id WHERE individual_location.individual_id=id ORDER BY individual_location.id DESC LIMIT 0,1;

        SELECT SUBSTRING_INDEX(persons.abbreviation, IF(LOCATE(',', persons.abbreviation), ',', ' '), 1) into person FROM collectors JOIN persons ON collectors.person_id=persons.id WHERE collectors.object_id=id AND collectors.object_type LIKE \"%Individual\" AND collectors.main=1;
        RETURN CONCAT(tag,' - ',person,' - ',location);
        END;");

        DB::unprepared('DROP FUNCTION IF EXISTS odb_ind_relativePosition');
        DB::unprepared("CREATE FUNCTION odb_ind_relativePosition(id INT)
          RETURNS VARCHAR(191) DETERMINISTIC
          BEGIN
          DECLARE position VARCHAR(191);
          SELECT AsText(relative_position) into position FROM individual_location WHERE individual_id=id ORDER BY individual_location.id DESC LIMIT 0,1;
          RETURN position;
          END;");


          DB::unprepared('DROP FUNCTION IF EXISTS odb_voucher_fullname');
          DB::unprepared("CREATE FUNCTION odb_voucher_fullname(id INT,number  VARCHAR(191),individual_id INT, biocollection_id INT)
          RETURNS VARCHAR(191) DETERMINISTIC
          BEGIN
            DECLARE checkcollector BOOLEAN DEFAULT false;
            DECLARE person,tagnum,biocol VARCHAR(191) DEFAULT '';
            SELECT 1 INTO checkcollector FROM collectors WHERE object_id=id AND object_type LIKE \"%Voucher\" AND main=1;
            IF (checkcollector=1) THEN
              SELECT SUBSTRING_INDEX(persons.abbreviation, IF(LOCATE(',', persons.abbreviation), ',', ' '), 1) into person FROM collectors JOIN persons ON collectors.person_id=persons.id WHERE collectors.object_id=id AND collectors.object_type LIKE \"%Voucher\" AND collectors.main=1;
              SET tagnum=number;
            ELSE
              SELECT SUBSTRING_INDEX(persons.abbreviation, IF(LOCATE(',', persons.abbreviation), ',', ' '), 1) into person FROM collectors JOIN persons ON collectors.person_id=persons.id WHERE collectors.object_id=individual_id AND collectors.object_type LIKE \"%Individual\" AND collectors.main=1;
              SELECT tag into tagnum FROM individuals WHERE individuals.id=individual_id;
            END IF;
            SELECT `acronym` into biocol FROM biocollections WHERE biocollections.id=biocollection_id;
            RETURN CONCAT(tagnum,' - ',person,' - ',biocol,'');
        END;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      DB::unprepared('DROP FUNCTION IF EXISTS odb_ind_fullname');
      DB::unprepared('DROP FUNCTION IF EXISTS odb_ind_relativePosition');
      DB::unprepared('DROP FUNCTION IF EXISTS odb_voucher_fullname');

    }
}
