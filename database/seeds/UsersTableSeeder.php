<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\App\User::count() > 5) return;
	    $faker = Faker\Factory::create();
        $persons = App\Person::all();

        try {
        DB::table('users')->insert([
            'email' => 'user@example.org',
            'password' => bcrypt('password1'),
            'access_level' => 1,
        ]);
        } catch (Exception $e) {}

        for ($i = 0; $i < 40; $i++) {
		    $person = null;
		    if($faker->numberBetween(1,3) == 1) 
			    $person = $persons->random()->id;
            DB::table('users')->insert([
                'email' => $faker->email,
                'password' => "Locked",
                'access_level' => $faker->numberBetween(0,2),
                'person_id' => $person,
            ]);
	    }
    }
}
