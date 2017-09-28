<?php

use Illuminate\Database\Seeder;

/// TODO: Seeds for color, categorical and links

class MeasurementsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        if (\App\Measurement::count()) return;
	    $faker = Faker\Factory::create();

        $datasets = \App\Dataset::all();
        $persons = \App\Person::all();
        $taxons = \App\Taxon::valid()->get();
        $plants = \App\Plant::all();
        $vouchers = \App\Voucher::all();
        $references = \App\BibReference::all();
        $locations = \App\Location::where('adm_level', \App\Location::LEVEL_PLOT)->orWhere('adm_level', \App\Location::LEVEL_POINT)->get();
        $odbtraits = \App\ODBTrait::with('object_types')->get();

        foreach($odbtraits as $odbtrait) {
            unset($val);
            for ($i = 0; $i < 800; $i++) {
                switch ($odbtrait->type) {
                    // TODO: here
                case 0:
                case 1:
                    $val = $faker->randomNumber(5);
                    break;
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                }
                if (!isset($val)) continue;
                $otype = collect($odbtrait->object_types)->random()->object_type;
                switch($otype) {
                case "App\Plant":
                    $object = $plants->random();
                    break;
                case "App\Locations":
                    $object = $locations->random();
                    break;
                case "App\Voucher":
                    $object = $vouchers->random();
                    break;
                case "App\Taxon":
                    $object = $taxons->random();
                    break;
                }
                $measurement = new App\Measurement([
                    'trait_id' => $odbtrait->id,
                    'measured_type' => $otype,
                    'measured_id' => $object->id,
                    'date' => $faker->date,
                    'person_id' => $persons->random()->id,
                    'bibreference_id' => $references->random()->id,
                    'dataset_id' => $datasets->random()->id,
                ]);
                $measurement->valueActual = $val;
                $measurement->save();
            }
        }
    }
}
