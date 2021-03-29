<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

use App\Models\BibReference;

class BibReferencesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (BibReference::count()) {
            return;
        }
        $faker = Faker\Factory::create();

        for ($i = 0; $i < 50; ++$i) {
            $lname = $faker->lastName;
            $name = $lname.', '.strtoupper($faker->randomLetter).'.';
            if (1 == $faker->numberBetween(1, 4)) {
                $name = $name.' and '.$faker->lastName.', '.strtoupper($faker->randomLetter).'.';
            }
            $year = $faker->numberBetween($min = 1960, $max = 2016);
            $title = $faker->sentence;
            $ftitle = strtok($title, ' ');
            DB::table('bib_references')->insert([ //,
            'bibtex' => '@article{'.strtolower($lname.$year.$ftitle).',author={'.$name.'},title={'.$title.'},year='.$year.
            ',journal={'.$faker->sentence.'}}',
            ]);
        }
    }
}
