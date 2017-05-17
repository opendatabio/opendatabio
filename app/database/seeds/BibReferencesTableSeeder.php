<?php

use Illuminate\Database\Seeder;

class BibReferencesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    $faker = Faker\Factory::create();

	    for ($i = 0; $i < 5; $i++) {
		    $lname = $faker->lastName;
		    $name = $lname  . ", ".strtoupper($faker->randomLetter) . ".";
		    $year = $faker->numberBetween($min = 1960, $max = 2016);
            DB::table('bib_references')->insert([ //,
		    'bibtex' => '@article{'.$lname.$year.',author={'.$name.'},title={'.$faker->sentence.'},year='.$year.
		    ',journal={'.$faker->sentence.'}}',
            ]);
	    }
    }
}
