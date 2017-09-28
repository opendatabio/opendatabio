<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Tests\IncompleteDateClass;

class IncompleteDateTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasic()
    {
        $obj = new IncompleteDateClass;
        $obj->setDate(11, 10, 2012);
        // just sanity...
        $this->assertEquals($obj->date, "2012-11-10");
        // basic interface
        $this->assertEquals($obj->getDayAttribute(), 10);
        $this->assertEquals($obj->getMonthAttribute(), 11);
        $this->assertEquals($obj->getYearAttribute(), 2012);
        // This should work by Eloquent "magic"
        $this->assertEquals($obj->day, "10");
        $this->assertEquals($obj->month, "11");
        $this->assertEquals($obj->year, "2012");
    }
    public function testFormat() 
    {
        $obj = new IncompleteDateClass;
        $obj->date = "2012-11-10";
        $this->assertEquals($obj->formatDate, "2012-11-10");
        $obj->date = "2012-11-00";
        $this->assertEquals($obj->formatDate, "2012-11");
        $obj->date = "2012-00-00";
        $this->assertEquals($obj->formatDate, "2012");
    }
    public function testValid() 
    {
        // valid dates:
        $this->assertTrue(IncompleteDateClass::checkDate(12, 31, 2017));
        $this->assertTrue(IncompleteDateClass::checkDate(12, 00, 2017));
        $this->assertTrue(IncompleteDateClass::checkDate(00, 00, 2017));
        // array invocation
        $this->assertTrue(IncompleteDateClass::checkDate([01, 01, 2017]));
        // invalid date (31 / feb)
        $this->assertFalse(IncompleteDateClass::checkDate(02, 31, 2017));
        // invalid month
        $this->assertFalse(IncompleteDateClass::checkDate(13, 00, 2017));
        // if month is unknown, day must be unknown too
        $this->assertFalse(IncompleteDateClass::checkDate(00, 31, 2017));
    }
    public function testBefore() 
    {
        // equal dates
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar([01,01,1970], [01,01,1970]));
        // string invocation
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar("1970-01-01", "1970-01-01"));
        // full dates
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar([12,31,1969], [01,01,1970]));
        // full dates
        $this->assertFalse(IncompleteDateClass::beforeOrSimilar([01,01,1970], [12,31,1969]));

        // incomplete second, known year
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar([12,31,1969], [0,0,1969]));
        // incomplete second, known month
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar([12,31,1969], [12,0,1969]));
        // incomplete second, known year
        $this->assertFalse(IncompleteDateClass::beforeOrSimilar([01,01,1970], [0,0,1969]));
        // incomplete second, known month
        $this->assertFalse(IncompleteDateClass::beforeOrSimilar([31,12,1969], [11,0,1969]));

        // incomplete first, known year
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar([00,00,1969], [12,31,1969]));
        // incomplete first, known month
        $this->assertTrue(IncompleteDateClass::beforeOrSimilar([12,00,1969], [12,1,1969]));
        // incomplete first, known year
        $this->assertFalse(IncompleteDateClass::beforeOrSimilar([00,00,1970], [12,31,1969]));
        // incomplete first, known month
        $this->assertFalse(IncompleteDateClass::beforeOrSimilar([12,00,1969], [11,31,1969]));
    }
}