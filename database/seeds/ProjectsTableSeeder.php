<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

class ProjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (\App\Project::count() > 5) {
            return;
        }
        $faker = Faker\Factory::create();
        $users = App\User::all();

        for ($i = 0; $i < 40; ++$i) {
            $project = App\Project::create([
                'name' => $faker->sentence(5),
                'privacy' => $faker->numberBetween(0, 2),
            ]);
            for ($j = 0; $j < $faker->numberBetween(3, 10); ++$j) {
                try {
                    $project->users()->attach($users->random()->id, ['access_level' => $faker->numberBetween(0, 2)]);
                } catch (Exception $e) {
                } // duplicate?
            }
        }
    }
}
