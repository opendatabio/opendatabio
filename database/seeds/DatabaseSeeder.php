<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PersonsTableSeeder::class); // depends on populated Herbaria
        $this->call(BibReferencesTableSeeder::class);
        $this->call(LocationsTableSeeder::class);
        $this->call(TaxonsTableSeeder::class); // depends on populated Persons
    }
}
