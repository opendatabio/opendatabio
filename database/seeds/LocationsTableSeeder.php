<?php

use Illuminate\Database\Seeder;
use App\Location;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    $l = new Location(['name' => 'Brasil', 'adm_level' => 1]);
	    $l->geom = "POLYGON((-34.9 -6, -51.8 3.7, -69.7 0.2, -71.8 -9.8, -53.9 -26.3, -57.4 -30.6, -52.3 -33.1, -34.9 -6))";
	    $l->save();
    }
}
