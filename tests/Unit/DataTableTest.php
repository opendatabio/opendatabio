<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests\Unit;

use Tests\TestCase;
use Auth;

class DataTableTest extends TestCase
{
    /**
     * Are the datatables queries working?
     */
    public function testSimple()
    {
        Auth::loginUsingId(1);
        $dt = (new \App\DataTables\DatasetsDataTable())->query();
        $this->assertEquals($dt->count(), \App\Dataset::count());

        $dt = (new \App\DataTables\BibReferenceDataTable())->query();
        $this->assertEquals($dt->count(), \App\BibReference::count());

        $dt = (new \App\DataTables\LocationsDataTable())->query();
        $this->assertEquals($dt->count(), \App\Location::count());

        $dt = (new \App\DataTables\PersonsDataTable())->query();
        $this->assertEquals($dt->count(), \App\Person::count());

        $dt = (new \App\DataTables\PlantsDataTable())->query();
        $this->assertEquals($dt->count(), \App\Plant::count());
        // is plant datatatable working with parameters?
        $location = \App\Plant::first()->location_id;
        $dtP = (new \App\DataTables\PlantsDataTable())->with('location', $location)->query();
        $this->assertEquals($dtP->count(), \App\Plant::where('location_id', $location)->count());

        $dt = (new \App\DataTables\ProjectsDataTable())->query();
        $this->assertEquals($dt->count(), \App\Project::count());

        $dt = (new \App\DataTables\TaxonsDataTable())->query();
        $this->assertEquals($dt->count(), \App\Taxon::count());

        $dt = (new \App\DataTables\TraitsDataTable())->query();
        $this->assertEquals($dt->count(), \App\ODBTrait::count());

        $dt = (new \App\DataTables\VouchersDataTable())->query();
        $this->assertEquals($dt->count(), \App\Voucher::count());
        // is voucher datatatable working with the delicate person parameter?
        $person = \App\Voucher::first()->person_id;
        $dtP = (new \App\DataTables\VouchersDataTable())->with('person', $person)->query()->get();
        $this->assertGreaterThan(0, $dtP->count());

        $dt = (new \App\DataTables\UsersDataTable())->query();
        $this->assertEquals($dt->count(), \App\User::count());

        // measurements require a parameter
        $plant = \App\Measurement::where('measured_type', 'App\Plant')->first()->measured_id;
        $dt = (new \App\DataTables\MeasurementsDataTable())->with(['measured_type' => 'App\Plant', 'measured' => $plant])->query();
        $this->assertEquals($dt->count(), \App\Measurement::where('measured_type', 'App\Plant')->where('measured_id', $plant)->count());
    }
}
