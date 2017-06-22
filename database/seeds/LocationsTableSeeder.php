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
	    Location::getQuery()->delete();
	    $br = new Location(['name' => 'Brasil', 'adm_level' => 0]);
	    $br->geom = "POLYGON((-34.9 -6, -51.8 3.7, -69.7 0.2, -71.8 -9.8, -53.9 -26.3, -57.4 -30.6, -52.3 -33.1, -34.9 -6))";
	    $br->save();

	    $sp = new Location(['name' => 'São Paulo', 'adm_level' => 1, 'parent_id' => $br->id]);
	    $sp->geom = "POLYGON((-46.3 -24, -47.4 -20, -50.6 -19.8, -52.9 -22.5, -48 -25.3, -46.3 -24))";
	    $sp->save();

	    $am = new Location(['name' => 'Amazonas', 'adm_level' => 1, 'parent_id' => $br->id]);
	    $am->geom = "POLYGON((-69.7 0.2, -71.8 -9.8, -58.5 -9.4, -56.4 -2.2, -69.7 0.2))";
	    $am->save();

	    $ma = new Location(['name' => 'Manaus', 'adm_level' => 2, 'parent_id' => $am->id]);
	    $ma->geom = "POLYGON(( -59.978786  -3.163954, -59.820858 -3.041910, -60.038524 -2.929453, -60.112682 -3.056309, -59.978786  -3.163954 ))";
	    $ma->save();
    }
}
