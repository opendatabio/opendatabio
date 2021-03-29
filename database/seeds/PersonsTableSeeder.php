<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;
use App\Models\Herbarium;

class PersonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (\App\Models\Person::count()) {
            return;
        }

        $faker = Faker\Factory::create();

        for ($i = 0; $i < 40; ++$i) {
            $name = $faker->name;
            $abb = explode(' ', trim(strtoupper($name)));
            $lname = array_pop($abb);
            for ($j = 0; $j < sizeof($abb); ++$j) {
                $abb[$j] = substr($abb[$j], 0, 1).'.';
            }
            $abb = $lname.', '.join(' ', $abb);
            $herbarium = null;
            if (1 == $faker->numberBetween(1, 4)) {
                $herbarium = Herbarium::all()->random()->id;
            }
            try {
                DB::table('persons')->insert([ //,
                'full_name' => $name,
                'abbreviation' => $abb,
        'email' => $faker->email,
        'herbarium_id' => $herbarium,
    ]);
            } catch (Illuminate\Database\QueryException $e) {
            } // duplicate abbrev?
        }
    }
}
