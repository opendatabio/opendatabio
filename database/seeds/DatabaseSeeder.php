<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->call(PersonsTableSeeder::class); // depends on populated Herbaria
        $this->call(UsersTableSeeder::class); // depends on populated Persons
        $this->call(ProjectsTableSeeder::class); // depends on populated Users
        $this->call(BibReferencesTableSeeder::class);
        $this->call(DatasetsTableSeeder::class); // depends on populated Users, BibRefs, Tags
        $this->call(LocationsTableSeeder::class);
        $this->call(TaxonsTableSeeder::class); // depends on populated Persons
        $this->call(PlantsTableSeeder::class); // depends on populated Persons, Locations, Taxons, Projects, Herbaria
        $this->call(VouchersTableSeeder::class); // depends on populated Persons, Locations, Taxons, Projects, Herbaria, Plants
        $this->call(TraitsTableSeeder::class);
        $this->call(MeasurementsTableSeeder::class); // depends on populated Persons, BibRefs, Traits, Datasets, Locations, Taxons, Plants, Vouchers
    }
}
