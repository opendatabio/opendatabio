<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace Tests\Unit;

use Tests\TestCase;
use Auth;
use App\Location;
use App\Plant;
use Carbon\Carbon;

class PointQuadrantTest extends TestCase
{
    /** Used to clean up database after tests (successfull or not!) */
    public function tearDown()
    {
        Auth::loginUsingId(1);
        Location::where('name', 'Test Plot')->orWhere('name', 'Test Point')->get()->map(function ($item, $key) { return $item->plants()->delete(); });
        Location::where('name', 'Test Plot')->orWhere('name', 'Test Point')->delete();
    }

    public function testSimple()
    {
        Auth::loginUsingId(1);
        // creates a plant on a plot, sets its relative position, and retrieves it
        $plot = Location::create(['name' => 'Test Plot', 'adm_level' => Location::LEVEL_PLOT]);
        $plot_plant = Plant::create(['tag' => '1', 'location_id' => $plot->id, 'date' => Carbon::now(), 'project_id' => \App\Project::first()->id]);

        // Let's make sure the functions work with no relative position:
        $plot_plant = Plant::find($plot_plant->id);
        $this->assertEquals('', $plot_plant->relativePosition);
        $this->assertEquals(false, $plot_plant->x);
        $this->assertEquals(false, $plot_plant->y);
        $this->assertEquals(false, $plot_plant->angle);
        $this->assertEquals(false, $plot_plant->distance);

        $plot_plant->setRelativePosition(-3, 4);
        $plot_plant->save();
        // for some obscure reason, fresh is giving weird problems
        $plot_plant = Plant::find($plot_plant->id);

        $this->assertEquals('POINT(4 -3)', $plot_plant->relativePosition);
        $this->assertEquals(-3, $plot_plant->x);
        $this->assertEquals(4, $plot_plant->y);
        $this->assertEquals(126.86989764584402, $plot_plant->angle);
        $this->assertEquals(5, $plot_plant->distance);

        // creates a plant on a point, sets its relative position as angle / distance, and retrieves it
        $point = Location::create(['name' => 'Test Point', 'adm_level' => Location::LEVEL_POINT]);
        $point_plant = Plant::create(['tag' => '1', 'location_id' => $point->id, 'date' => Carbon::now(), 'project_id' => \App\Project::first()->id]);
        $point_plant->setRelativePosition(150, 4);
        $point_plant->save();
        // for some obscure reason, fresh is giving weird problems
        $point_plant = Plant::find($point_plant->id);

        $this->assertEquals('POINT(2 -3.4641016151378)', $point_plant->relativePosition);
        $this->assertEquals(-3.4641016151378, $point_plant->x);
        $this->assertEquals(2, $point_plant->y);
        $this->assertEquals(150, $point_plant->angle);
        $this->assertEquals(4, $point_plant->distance);
    }
}
