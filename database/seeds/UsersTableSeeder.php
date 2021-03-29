<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (\App\Models\User::count() > 5) {
            return;
        }
        $faker = Faker\Factory::create();
        $persons = App\Models\Person::all();

        try {
            DB::table('users')->insert([
            'email' => 'user@example.org',
            'password' => bcrypt('password1'),
            'access_level' => 1,
        ]);
        } catch (Exception $e) {
        }

        for ($i = 0; $i < 40; ++$i) {
            $person = null;
            if (1 == $faker->numberBetween(1, 3)) {
                $person = $persons->random()->id;
            }
            DB::table('users')->insert([
                'email' => $faker->email,
                'password' => 'Locked',
                'access_level' => $faker->numberBetween(0, 2),
                'person_id' => $person,
            ]);
        }
    }
}
