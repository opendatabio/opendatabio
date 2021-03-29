<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

class PlantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected function addCollectors($plant, $faker, $persons)
    {
        for ($i = 0; $i < $faker->numberBetween(0, 4); ++$i) {
            try {
                App\Models\Collector::create([
                'person_id' => $persons->random()->id,
                'object_type' => 'App\Models\Plant',
                'object_id' => $plant->id,
            ]);
            } catch (Exception $e) {
            }
        }
    }

    protected function Identify($plant, $faker, $persons, $taxons)
    {
        $modifier = 0 == $faker->numberBetween(0, 20) ?
                $faker->numberBetween(1, 5) : 0;
        App\Models\Identification::create([
                'person_id' => $persons->random()->id,
                'taxon_id' => $taxons->random()->id,
                'object_id' => $plant->id,
                'object_type' => 'App\Models\Plant',
                'date' => Carbon\Carbon::now(),
                'modifier' => $modifier,
            ]);
    }

    public function run()
    {
        // To make sure that we are seeing all registered plants
        Auth::loginUsingId(1);
        if (\App\Models\Plant::count()) {
            return;
        }
        $faker = Faker\Factory::create();
        $projects = \App\Models\Project::all();
        $persons = \App\Models\Person::all();
        $taxons = \App\Models\Taxon::valid()->leaf()->get();
        $plots = \App\Models\Location::where('adm_level', \App\Models\Location::LEVEL_PLOT)->get();
        $points = \App\Models\Location::where('adm_level', \App\Models\Location::LEVEL_POINT)->get();
        // on points
        foreach ($points as $point) {
            $plant = new \App\Models\Plant([
                'location_id' => $point->id,
                'tag' => '1',
                'date' => Carbon\Carbon::now(),
                'project_id' => $projects->random()->id,
            ]);
            $plant->save();
            $this->addCollectors($plant, $faker, $persons);
            $this->Identify($plant, $faker, $persons, $taxons);
        }
        // on plots
        for ($i = 0; $i < 1000; ++$i) {
            $plot = $plots->random();
            $plant = new \App\Models\Plant([
                'location_id' => $plot->id,
                'tag' => $i,
                'date' => Carbon\Carbon::now(),
                'project_id' => $projects->random()->id,
                'relative_position' => DB::raw('GeomFromText(\'POINT('.
                    $faker->numberBetween(0, $plot->y).' '.
                    $faker->numberBetween(0, $plot->x).')\')'),
            ]);
            $plant->save();
            $this->addCollectors($plant, $faker, $persons);
            $this->Identify($plant, $faker, $persons, $taxons);
        }
    }
}
