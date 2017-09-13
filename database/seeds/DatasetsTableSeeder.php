<?php

use Illuminate\Database\Seeder;

class DatasetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(\App\Dataset::count() > 5) return;
        $faker = Faker\Factory::create();
        $users = App\User::all();
        $tags = App\Tag::all();

        for ($i = 0; $i < 40; $i++) {
            $dataset = App\Dataset::create([
                'name' => $faker->sentence(3),
                'privacy' => $faker->numberBetween(0,2),
            ]);
            for ($j = 0; $j < $faker->numberBetween(3,10); $j++) {
                try {
                    $dataset->users()->attach($users->random()->id, ['access_level' => $faker->numberBetween(0,2), ]);
                } catch (Exception $e) {} // duplicate?
            }
            for ($j = 0; $j < $faker->numberBetween(1,3); $j++) {
                try {
                    $dataset->tags()->save($tags->random());
                } catch (Exception $e) {} // duplicate?
            }
	    }
    }
}
