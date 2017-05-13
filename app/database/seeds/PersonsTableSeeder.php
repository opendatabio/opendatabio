<?php

use Illuminate\Database\Seeder;

class PersonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    $faker = Faker\Factory::create();

	    $limit = 33;

	    for ($i = 0; $i < 20; $i++) {

		    $name = $faker->name;
		    $abb = explode(" ", trim(strtoupper($name)));
		    $lname = array_pop($abb);
		    for ($j = 0; $j < sizeof($abb); $j++) {
				$abb[$j] = substr($abb[$j], 0, 1) . ".";
		    }
		    $abb = $lname . ", " . join(" ", $abb);
            DB::table('persons')->insert([ //,
                'full_name' => $name,
                'abbreviation' => $abb,
                'email' => $faker->email
            ]);
	    }
    }
}
