<?php

use Illuminate\Database\Seeder;

class PlantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function addCollectors($plant, $faker, $persons) {
        for ($i = 0; $i < $faker->numberBetween(0, 4); $i++) {
            try {
            App\Collector::create([
                'person_id' => $persons->random()->id,
                'object_type' => 'App\Plant',
                'object_id' => $plant->id,
            ]);
            } catch (Exception $e) {}
        }
    }
    protected function Identify($plant, $faker, $persons, $taxons) {
            $modifier = $faker->numberBetween(0, 20) == 0 ?
                $faker->numberBetween(1, 5) : 0;
            App\Identification::create([
                'person_id' => $persons->random()->id,
                'taxon_id' => $taxons->random()->id,
                'object_id' => $plant->id,
                'object_type' => 'App\Plant',
                'date' => Carbon\Carbon::now(),
                'modifier' => $modifier,
            ]);
    }
    public function run()
    {
        if (\App\Plant::count()) return;
	    $faker = Faker\Factory::create();
        $projects = \App\Project::all();
        $persons = \App\Person::all();
        $taxons = \App\Taxon::valid()->leaf()->get();
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
            $this->addCollectors($plant, $faker, $persons);
            $this->Identify($plant, $faker, $persons, $taxons);
        } 
        // on plots
        for ($i = 0; $i < 1000; $i++) {
            $plot = $plots->random();
            $plant = new \App\Plant([
                'location_id' => $plot->id,
                'tag' => $i,
                'date' => Carbon\Carbon::now(),
                'project_id' => $projects->random()->id,
                'relative_position' => DB::raw('GeomFromText(\'POINT(' . 
                    $faker->numberBetween(0, $plot->y) . ' ' .
                    $faker->numberBetween(0, $plot->x) . ')\')'),
            ]);
            $plant->save();
            $this->addCollectors($plant, $faker, $persons);
            $this->Identify($plant, $faker, $persons, $taxons);
        }
    }
}
