<?php

use Illuminate\Database\Seeder;

class PlantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    $faker = Faker\Factory::create();
        $projects = \App\Project::all();
        $plots = \App\Location::where('adm_level', \App\Location::LEVEL_PLOT)->get();
        $points = \App\Location::where('adm_level', \App\Location::LEVEL_POINT)->get();
        // on points
        foreach($points as $point) {
            $plant = new \App\Plant([
                'location_id' => $point->id,
                'tag' => '1',
                'date' => Carbon\Carbon::now(),
                'project_id' => $projects->random()->id,
            ]);
            $plant->save();
        }
        // on plots
        for ($i = 0; $i < 1000; $i++) {
            $plot = $plots->random();
            $plant = new \App\Plant([
                'location_id' => $plot->id,
                'tag' => $i,
                'date' => Carbon\Carbon::now(),
                'project_id' => $projects->random()->id,
            ]);
            $plant->relativePosition = "POINT (" . $faker->numberBetween(0, $plot->y) . " " .
                                                $faker->numberBetween(0, $plot->x) . ")";
            $plant->save();

        }
    }
}
