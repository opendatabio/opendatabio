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

	    for ($i = 0; $i < 15; $i++) {
		    $lname = $faker->lastName;
		    $name = $lname  . ", ".strtoupper($faker->randomLetter) . ".";
		    if ($faker->numberBetween(1,4) == 1)
			    $name = $name . " and " . $faker->lastName . ", ".strtoupper($faker->randomLetter) . ".";
		    $year = $faker->numberBetween($min = 1960, $max = 2016);
		    $title = $faker->sentence; $ftitle = strtok($title, ' ');
            DB::table('bib_references')->insert([ //,
		    'bibtex' => '@article{'.strtolower($lname.$year.$ftitle).',author={'.$name.'},title={'.$title.'},year='.$year.
		    ',journal={'.$faker->sentence.'}}',
            ]);
	    }
    }
}
