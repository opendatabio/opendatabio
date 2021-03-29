<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

/// TODO: Seeds for color

class MeasurementsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Auth::loginUsingId(1);
        if (\App\Models\Measurement::count()) {
            return;
        }
        $faker = Faker\Factory::create();

        $datasets = \App\Models\Dataset::all();
        $persons = \App\Models\Person::all();
        $taxons = \App\Models\Taxon::valid()->get();
        $plants = \App\Models\Plant::all();
        $vouchers = \App\Models\Voucher::all();
        $references = \App\Models\BibReference::all();
        $locations = \App\Models\Location::where('adm_level', \App\Models\Location::LEVEL_PLOT)->orWhere('adm_level', \App\Models\Location::LEVEL_POINT)->get();
        $odbtraits = \App\Models\ODBTrait::with('object_types')->get();

        foreach ($odbtraits as $odbtrait) {
            unset($val);
            for ($i = 0; $i < 200; ++$i) {
                switch ($odbtrait->type) {
                    // TODO: here
                case 0:
                case 1:
                    $val = $faker->randomNumber(5);
                    break;
                case 2:
                case 3:
                case 4:
                    $val = $odbtrait->categories->random()->id;
                    break;
                case 5:
                    $val = $faker->sentence(5);
                    break;
                case 6:
                case 7:
                    $val = 'FILLER'; // just to pass "isset" below, will be randomized later
                }
                if (!isset($val)) {
                    continue;
                }
                $otype = collect($odbtrait->object_types)->random()->object_type;
                switch ($otype) {
                case "App\Models\Plant":
                    $object = $plants->random();
                    break;
                case "App\Models\Location":
                    $object = $locations->random();
                    break;
                case "App\Models\Voucher":
                    $object = $vouchers->random();
                    break;
                case "App\Models\Taxon":
                    $object = $taxons->random();
                    break;
                }
                $measurement = new App\Models\Measurement([
                    'trait_id' => $odbtrait->id,
                    'measured_type' => $otype,
                    'measured_id' => $object->id,
                    'date' => $faker->date,
                    'person_id' => $persons->random()->id,
                    'bibreference_id' => $references->random()->id,
                    'dataset_id' => $datasets->random()->id,
                ]);
                $measurement->save();
                if (7 == $odbtrait->type) {
                    $measurement->value = $faker->randomNumber(2);
                    switch ($odbtrait->link_type) {
                    case "App\Models\Taxon":
                        $measurement->value_i = $taxons->random()->id;
                        break;
                    }
                } else {
                    $measurement->valueActual = $val;
                }
                $measurement->save();
            }
        }
    }
}
