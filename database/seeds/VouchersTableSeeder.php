<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

class VouchersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected function addCollectors($voucher, $faker, $persons)
    {
        for ($i = 0; $i < $faker->numberBetween(0, 4); ++$i) {
            try {
                App\Collector::create([
                'person_id' => $persons->random()->id,
                'object_type' => 'App\Voucher',
                'object_id' => $voucher->id,
            ]);
            } catch (Exception $e) {
            }
        }
    }

    protected function addHerbaria($voucher, $faker, $herbaria)
    {
        $n = $faker->numberBetween(0, 2);
        for ($i = 0; $i < $n; ++$i) {
            try {
                $voucher->herbaria()->attach($herbaria->random(), ['herbarium_number' => $faker->bothify('??? ####')]);
            } catch (Exception $e) {
            }
        }
    }

    protected function Identify($voucher, $faker, $persons, $taxons)
    {
        $modifier = 0 == $faker->numberBetween(0, 20) ?
                $faker->numberBetween(1, 5) : 0;
        App\Identification::create([
                'person_id' => $persons->random()->id,
                'taxon_id' => $taxons->random()->id,
                'object_id' => $voucher->id,
                'object_type' => 'App\Voucher',
                'date' => Carbon\Carbon::now(),
                'modifier' => $modifier,
            ]);
    }

    public function run()
    {
	// to make sure that we are seeing the whole database
	Auth::loginUsingId(1);
        if (\App\Voucher::count()) {
            return;
        }
        $faker = Faker\Factory::create();
        $projects = \App\Project::all();
        $persons = \App\Person::all();
        $taxons = \App\Taxon::valid()->leaf()->get();
        $locations = \App\Location::whereIn('adm_level', [\App\Location::LEVEL_PLOT, \App\Location::LEVEL_POINT])->get();
        $plants = \App\Plant::all();
        $herbaria = \App\Herbarium::all();
        // on locations
        for ($i = 0; $i < 100; ++$i) {
            $voucher = new \App\Voucher([
                'parent_id' => $locations->random()->id,
                'parent_type' => 'App\Location',
                'person_id' => $persons->random()->id,
                'number' => $faker->randomNumber(4),
                'date' => Carbon\Carbon::now(),
                'project_id' => $projects->random()->id,
            ]);
            try {
                $voucher->save();
                $this->addCollectors($voucher, $faker, $persons);
                $this->addHerbaria($voucher, $faker, $herbaria);
                $this->Identify($voucher, $faker, $persons, $taxons);
            } catch (Exception $e) {
            }
        }
        // on plants
        for ($i = 0; $i < 100; ++$i) {
            $plant = $plants->random();
            $voucher = new \App\Voucher([
                'parent_id' => $plant->id,
                'parent_type' => 'App\Plant',
                'person_id' => $persons->random()->id,
                'number' => $faker->randomNumber(4),
                'date' => Carbon\Carbon::now(),
                'project_id' => $plant->project_id,
            ]);
            try {
                $voucher->save();
                $this->addHerbaria($voucher, $faker, $herbaria);
                $this->addCollectors($voucher, $faker, $persons);
            } catch (Exception $e) {
            }
        }
    }
}
