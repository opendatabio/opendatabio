<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;
use App\ODBTrait;
use App\UserTranslation;

class TraitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (ODBTrait::count()) {
            return;
        }

        $faker = Faker\Factory::create();

        $basetraits = collect(['Height', 'Volume', 'Diameter', 'Richness', 'Evenness', 'Abundance', 'Fertility']);
        $units = collect(['m', 'kg', 'cm²', 'm³', 'l', 'ø']);
        for ($i = 0; $i < 40; ++$i) {
            $person = $faker->lastName();
            $trait = $basetraits->random();
            // TODO: seeds for color traits
            $type = $faker->numberBetween(0, 6);
            if (6 == $type) {
                $type = 7;
	    }
	    $export_name = strtolower($trait.'_'.$person);
	    if (!ODBTrait::where('export_name', $export_name)->count()) {
            $t = ODBTrait::create([
                'type' => $type,
                'export_name' => $export_name,
                'unit' => $type < 2 ? $units->random() : null,
                'range_min' => $type < 2 ? $faker->numberBetween(-200, 200) : null,
                'range_max' => $type < 2 ? $faker->numberBetween(400, 1000) : null,
                'link_type' => 7 == $type ? collect(ODBTrait::LINK_TYPES)->random() : null,
	]);
	}
            UserTranslation::create(['translatable_id' => $t->id,
                'translatable_type' => 'App\\ODBTrait',
                'language_id' => '1',
                'translation_type' => '0',
                'translation' => $trait.' by '.$person.'\'s method',
            ]);
            $nob = $faker->numberBetween(1, 3);
            for ($j = 0; $j < $nob; ++$j) {
                try {
                    $t->object_types()->create(['object_type' => collect(ODBTrait::OBJECT_TYPES)->random()]);
                } catch (Exception $e) {
                }
            }
            if ($type > 1 and $type < 5) {
                $ncat = $faker->numberBetween(3, 10);
                for ($j = 1; $j <= $ncat; ++$j) {
                    $cat = $t->categories()->create(['rank' => $j]);
                    UserTranslation::create(['translatable_id' => $cat->id,
                    'translatable_type' => 'App\\TraitCategory',
                    'language_id' => '1',
                    'translation_type' => '0',
                    'translation' => $trait.' category '.$j,
                ]);
                }
            }
        }
    }
}
