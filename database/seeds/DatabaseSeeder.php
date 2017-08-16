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
        $this->call(UsersTableSeeder::class); // depends on populated Persons
        $this->call(ProjectsTableSeeder::class); // depends on populated Users
        $this->call(BibReferencesTableSeeder::class);
        $this->call(LocationsTableSeeder::class);
        $this->call(TaxonsTableSeeder::class); // depends on populated Persons
        $this->call(PlantsTableSeeder::class); // depends on populated Persons, Locations, Taxons, Projects
    }
}
