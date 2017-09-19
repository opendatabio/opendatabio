<?php

use Illuminate\Database\Seeder;
use App\ODBTrait;
use App\UserTranslation;

class TraitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (ODBTrait::count()) return;

        $faker = Faker\Factory::create();

        $basetraits = collect(['Height', 'Volume', 'Diameter', 'Richness', 'Evenness', 'Abundance', 'Fertility']);
        $units = collect(['m', 'kg', 'cm²', 'm³', 'l', 'ø']);
        for ($i = 0; $i < 30; $i++) {
            $person = $faker->lastName();
            $trait = $basetraits->random();
            $t = ODBTrait::create([
                'type' => $faker->numberBetween(0,7), 
                'export_name' => strtolower($trait . '_' . $person),
                'unit' => $units->random(),
                'range_min' => $faker->numberBetween(-200, 200),
                'range_max' => $faker->numberBetween(400, 1000),
                'link_type' => collect(ODBTrait::OBJECT_TYPES)->random(),
            ]);
            UserTranslation::create(['translatable_id' => $t->id,
                'translatable_type' => 'App\\ODBTrait',
                'language_id' => '1',
                'translation_type' => '0',
                'translation' => $trait . ' by ' . $person . '\'s method',
            ]);
            $nob = $faker->numberBetween(1,3);
            for ($j = 0; $j < $nob; $j++) {
                try {
                    $t->object_types()->create(['object_type' => collect(ODBTrait::OBJECT_TYPES)->random(), ]);
                } catch (Exception $e) {}
            }
            for ($j = 0; $j < 4; $j++) {
                $cat = $t->categories()->create(['rank' => $j, ]);
                UserTranslation::create(['translatable_id' => $cat->id,
                    'translatable_type' => 'App\\TraitCategory',
                    'language_id' => '1',
                    'translation_type' => '0',
                    'translation' => $trait . ' category ' . $j,
                ]);
            }
        }
    }
}
