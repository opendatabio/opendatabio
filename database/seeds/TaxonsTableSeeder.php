<?php

use Illuminate\Database\Seeder;
use App\Taxon;
use App\Person;

class TaxonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    $faker = Faker\Factory::create();
        // families
        $level = 120;
	    for ($i = 0; $i < 20; $i++) {
            $name = $faker->word;
            if (strlen($name) < 4) $name .= $faker->word;
            $name .= "ceae";
            
            Taxon::create([
                'name' => ucfirst($name),
                'level' => $level,
                'valid' => 1,
                'author' => $faker->lastName,
                'bibreference' => $faker->sentence(3) . " " . $faker->randomNumber(3),
            ]);
        }
        // genera
        $level = 180;
	    for ($i = 0; $i < 30; $i++) {
            $name = $faker->word;
            if (strlen($name) < 4) $name .= $faker->word;
            $name .= "ium";
            
            Taxon::create([
                'name' => ucfirst($name),
                'parent_id' => Taxon::where('level', 120)->get()->random()->id,
                'level' => $level,
                'valid' => 1,
                'author' => $faker->lastName,
                'bibreference' => $faker->sentence(3) . " " . $faker->randomNumber(3),
            ]);
        }
        // species
        $level = 210;
	    for ($i = 0; $i < 40; $i++) {
            $name = $faker->word;
            if (strlen($name) < 4) $name .= $faker->word;
            
            $sp = Taxon::create([
                'name' => $name,
                'parent_id' => App\Taxon::where('level', 180)->get()->random()->id,
                'level' => $level,
                'valid' => 1,
                'author' => $faker->lastName,
                'bibreference' => $faker->sentence(3) . " " . $faker->randomNumber(3),
            ]);
            // Specialists
            for ($j = 0; $j < $faker->numberBetween(0,3); $j++) {
                try {
                    $sp->persons()->attach(Person::all()->random());
                } catch (Exception $e) {} // duplicates?
            }

        }
        // subsp
	    for ($i = 0; $i < 10; $i++) {
            $parent = App\Taxon::where('level', 210)->get()->random()->id;
            for ($j = 0; $j < $faker->numberBetween(1,5); $j++) {
                $name = $faker->word;
                if (strlen($name) < 4) $name .= $faker->word;
                $sp = Taxon::create([
                    'name' => $name,
                    'parent_id' => $parent,
                    'level' => collect([220, 240, 270])->random(),
                    'valid' => 1,
                    'author' => $faker->lastName,
                    'bibreference' => $faker->sentence(3) . " " . $faker->randomNumber(3),
                ]);
                // Specialists
                for ($k = 0; $k < $faker->numberBetween(0,3); $k++) {
                    try {
                        $sp->persons()->attach(Person::all()->random());
                    } catch (Exception $e) {} // duplicates?
                }
            }
        }
        // invalid species
        $level = 210;
	    for ($i = 0; $i < 20; $i++) {
            $name = $faker->word;
            if (strlen($name) < 4) $name .= $faker->word;
            $senior = App\Taxon::where('level', 210)->get()->random()->id;
            $sp = Taxon::create([
                'name' => $name,
                'parent_id' => App\Taxon::where('level', 180)->get()->random()->id,
                'level' => $level,
                'valid' => 0,
                'senior_id' => $senior,
                'author' => $faker->lastName,
                'bibreference' => $faker->sentence(3) . " " . $faker->randomNumber(3),
            ]);
            // Specialists
            for ($j = 0; $j < $faker->numberBetween(0,3); $j++) {
                try {
                    $sp->persons()->attach(Person::all()->random());
                } catch (Exception $e) {} // duplicates?
            }
        }
    }
}
