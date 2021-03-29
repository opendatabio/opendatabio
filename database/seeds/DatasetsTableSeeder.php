<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

class DatasetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (\App\Models\Dataset::count() > 5) {
            return;
        }
        $faker = Faker\Factory::create();
        $users = App\Models\User::all();
        $tags = App\Models\Tag::all();
        $references = App\Models\BibReference::all();

        for ($i = 0; $i < 40; ++$i) {
            $dataset = App\Models\Dataset::create([
                'name' => $faker->sentence(3),
                'privacy' => $faker->numberBetween(0, 2),
                'bibreference_id' => (0 == $faker->numberBetween(0, 3) ? $references->random()->id : null),
            ]);
            for ($j = 0; $j < $faker->numberBetween(3, 10); ++$j) {
                try {
                    $dataset->users()->attach($users->random()->id, ['access_level' => $faker->numberBetween(0, 2)]);
                } catch (Exception $e) {
                } // duplicate?
            }
            for ($j = 0; $j < $faker->numberBetween(1, 3); ++$j) {
                try {
                    $dataset->tags()->save($tags->random());
                } catch (Exception $e) {
                } // duplicate?
            }
        }
    }
}
