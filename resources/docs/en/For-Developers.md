* [**For-Developers**](#)

# For Developers
The Laravel-Datatables library is incompatible with `php artisan serve`, so this command should not be used. The recommended way of running this app in development is by installing it and choosing "development" in the installer.

This system uses Laravel Mix to compile the SASS and JavaScript code used. If you would like to contribute to the app development, remember to run `npm run prod` after making any change to these files.

Notice that "doctrine/dbal" is being held back to 2.3 because of incompatibility with the ERD-generator package. All other packages are the most recent versions.

## Programming directives and naming conventions

1. This software should adhere to Semantic Versioning, starting from version 0.1.0-alpha1. The companion R package should follow a similar versioning scheme.
1. All variables and functions should be named in English, with entities and fields related to the database being named in the singular form. All tables (where appropriate) should have an "id" column, and foreign keys should reference the base table with "_id" suffix, except in cases of self-joins (such as "taxon.parent_id") or [polymorphic foreign keys](Core-Objects#polymorphicrelationships). The id of each table has type INT and should be autoincrementing.
1. There should be a structure to store which **Plugins** are installed on a given database and which are the compatible system versions.

## Documentation

Documentation should organized as markdown files within the `resources/docs/` folder, using the [LaRecipe](https://github.com/saleem-hadad/larecipe) package, which is configured to be multilingual instead of versioning. In addition to multilingual, installations of OpenDataBio may therefore customize their own documentation. Note that:
  * The OpenDataBio English version of the documentation is identical to the [GitHub Wiki](https://github.com/opendatabio/opendatabio/wiki) - the files have the same name and content except for:
    * a list of links on the top of each page used by LaRecipe to form the right-margin index;
    * the image sources are different, but both the local and GitHub links are placed in the App documentation files. Therefore, changes in the documentation should be made in the local files, which can then be copied to the GitHub wiki by just removing the top links.
    * File **API-Tester.md** makes no sense to place in the GitHub Wiki and should not be transfered. It is the only file using some of the LaRecipe special coding.  This implements [LaRecipe-Swagger](https://github.com/saleem-hadad/larecipe-swagger) package.
  * Most figures for explaining the model were generated using [Laravel ER Diagram Generator](https://github.com/beyondcode/laravel-er-diagram-generator), which allows to show all the methods implemented in each Class or Model and not only the table direct links:
    * These figures are in the `public/images` folder.
    * The  `config/erd-generator.php` file must customized to re-generate them, one by one. The configurations for each file is commented within this file below the file name. Note the option to show or not the tables structures, and depending on figure some graphviz options must also be adjusted.
    * To generate a file you must execute something like the following command:
    ```
    php artisan generate:erd public/images/trait_model.png
    ```

## Hierarchical Relations
The [Location](Core-Objects#locations) and the [Taxon](Core-Objects#locations) use the Nested Set Model implemented through the [Baum](https://github.com/etrepat/baum) package. Other Nested Set Model packages are available for Laravel and the used may not be the most stable and updated version, although it is working.

* The Hierarchical relation implemented through informing the parent-child paths for Locations and Taxons also means that queries for these objects have to query the descendants of any node. Ex: searching for 'Lauraceae' may need to return all the genera and species and infraspecies within this family. Similarly, searching form 'Brazil', should return data from all locations within Brazil. These is easly implemented with the Baum package and columns `lft` and `rgt` allow fast search for acenstors and descendants if a raw query is needed. Locations also have geometries, that could be used directly to query objects as well.

* Datatables for Locations and Taxon have counts for Individuals, Vouchers, Measurements and these counts MUST convey the sum of related objects to all Location and Taxon descendants. Depending on  the `depth` of the tree structure in each case, it may become too slow to render the datatable, even the initial server side page as they usually will be nodes with the largest number of descendants.

* There are different ways of retriving such counts. The code below may be run in `Tinker` to compare the different performance for such queries. Compare with or withoutGlobalScopes from the queries, i.e. including or excluding the check of permissions to individuals, vouchers and measurements to count them. When including global scopes you must be logged.

*  **withoutGlobalScopes**:

```php
$taxon_id=1; $project_id = 2;

//option 1 FASTER
$start = microtime(true);
$taxons_ids = Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
$individuals_count = Individual::whereHas('identification', function($query) use($taxons_ids) {
    $query->whereIn('identifications.taxon_id',$taxons_ids);})->withoutGlobalScopes()->count();
$end = microtime(true);
$time1 = ($end - $start);

//with project scopeLeaf
$start = microtime(true);
$taxons_ids = Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
$individuals_count = Individual::whereHas('identification', function($query) use($taxons_ids) {
    $query->whereIn('identifications.taxon_id',$taxons_ids);})->where('project_id',"=",$project_id)->withoutGlobalScopes()->count();
$end = microtime(true);
$time1p = ($end - $start);



//option 2
$start = microtime(true);
$tx = array_sum(Taxon::whereIn('id',$taxons_ids)->cursor()->map(function($taxon) { return $taxon->individuals()->withoutGlobalScopes()->count();})->toArray());
$end = microtime(true);
$time2 = ($end - $start);


$start = microtime(true);
$tx = array_sum(Taxon::whereIn('id',$taxons_ids)->cursor()->map(function($taxon) use($project_id) { return $taxon->individuals()->where('project_id','=',$project_id)->withoutGlobalScopes()->count();})->toArray());
$end = microtime(true);
$time2p = ($end - $start);


//option 3
$start = microtime(true);
$count = array_sum(Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->loadCount(['individuals' => function ($query) {
    $query->withoutGlobalScopes();
}])->pluck('individuals_count')->toArray());
$end = microtime(true);
$time3 = ($end - $start);

//with project scope
$start = microtime(true);
$count = array_sum(Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->loadCount(['individuals' => function ($query) use($project_id) {
    $query->withoutGlobalScopes()->where('project_id',$project_id);
}])->pluck('individuals_count')->toArray());
$end = microtime(true);
$time3p = ($end - $start);

```

* **With scopes**:

```php

auth::loginUsingID(2);
$taxon_id=1; $project_id = 2;

//option 1
$start = microtime(true);
$taxons_ids = Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
$individuals_count = Individual::whereHas('identification', function($query) use($taxons_ids) {
    $query->whereIn('identifications.taxon_id',$taxons_ids);})->count();
$end = microtime(true);
$time1ws = ($end - $start);

//with project scopeLeaf
$start = microtime(true);
$taxons_ids = Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
$individuals_count = Individual::whereHas('identification', function($query) use($taxons_ids) {
    $query->whereIn('identifications.taxon_id',$taxons_ids);})->where('project_id',"=",$project_id)->count();
$end = microtime(true);
$time1pws = ($end - $start);



//option 2
$start = microtime(true);
$tx = array_sum(Taxon::whereIn('id',$taxons_ids)->cursor()->map(function($taxon) { return $taxon->individuals()->count();})->toArray());
$end = microtime(true);
$time2ws = ($end - $start);


$start = microtime(true);
$tx = array_sum(Taxon::whereIn('id',$taxons_ids)->cursor()->map(function($taxon) use($project_id) { return $taxon->individuals()->where('project_id','=',$project_id)->count();})->toArray());
$end = microtime(true);
$time2pws= ($end - $start);


//option 3
$start = microtime(true);
$count = array_sum(Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->loadCount('individuals')->pluck('individuals_count')->toArray());
$end = microtime(true);
$time3ws = ($end - $start);

//with project scope
$start = microtime(true);
$count = array_sum(Taxon::where('id','=',$taxon_id)->first()->getDescendantsAndSelf()->loadCount(['individuals' => function ($query) use($project_id) {
    $query->where('project_id',$project_id);
}])->pluck('individuals_count')->toArray());
$end = microtime(true);
$time3pws = ($end - $start);

```


```php

echo
"| Option | Execution Time | withoutGlobalScopes | ProjectScope |
|----|----|----|
|Option 1|".round($time1,6)."| true | false |
|Option 2|".round($time2,6)."| true | false |
|Option 3|".round($time3,6)."| true | false |
|----|----|----|
|Option 1|".round($time1ws,6)."| false | false |
|Option 2|".round($time2ws,6)."| false | false |
|Option 3|".round($time3ws,6)."| false | false |
|----|----|----|
|Option 1|".round($time1p,6)."| true | true |
|Option 2|".round($time2p,6)."| true | true |
|Option 3|".round($time3p,6)."| true | true |
|----|----|----|
|Option 1|".round($time1pws,6)."| false | true |
|Option 2|".round($time2pws,6)."| false | true |
|Option 3|".round($time3pws,6)."| false | true |
";

```


| Option | Execution Time | withoutGlobalScopes | ProjectScope |
|----|----|----|
|Option 1|1.134222| true | false |
|Option 2|2.680581| true | false |
|Option 3|0.99281| true | false |
|----|----|----|
|Option 1|1.149312| false | false |
|Option 2|3.18036| false | false |
|Option 3|1.055677| false | false |
|----|----|----|
|Option 1|1.168094| true | true |
|Option 2|3.044003| true | true |
|Option 3|1.019828| true | true |
|----|----|----|
|Option 1|1.174584| false | true |
|Option 2|3.015961| false | true |
|Option 3|1.047753| false | true |


* For Taxon measurements which is a distant relationship and involves [polymorphic relations](Core-Objects#polymorphicrelationships) it isffast to get directly from measurements as in the Measurements data-table query but without scopes.

```php
$start = microtime(true);
$tx = Taxon::where('id',$taxon_id)->first()->getDescendantsAndSelf()->loadCount(
            ['individual_measurements' => function ($query) {
                $query->withoutGlobalScopes(); } ])->loadCount(['voucher_measurements' => function ($query) {
                    $query->withoutGlobalScopes(); } ])->loadCount(['measurements' => function ($query) {
                        $query->withoutGlobalScopes(); } ]);
$n1 = array_sum($tx->pluck('voucher_measurements_count')->toArray());
$n2 = array_sum($tx->pluck('individual_measurements_count')->toArray());
$n3 = array_sum($tx->pluck('measurements_count')->toArray());
($n1+$n2+$n3);
$end = microtime(true);
$time1 = ($end - $start);

$start = microtime(true);
$taxon_list = Taxon::find($taxon_id)->getDescendantsAndSelf()->pluck('id')->toArray();
$query = Measurement::whereHasMorph('measured',['App\Individual','App\Voucher'],function($mm) use($taxon_list) { $mm->withoutGlobalScopes()->whereHas('identification',function($idd) use($taxon_list)  { $idd->whereIn('taxon_id',$taxon_list);});});
$query = $query->orWhereRaw('measured_type = "App\Taxon" AND measured_id='.$taxon_id);
$query->count();
$end = microtime(true);
$time2 = ($end - $start);

echo
"| Option | Execution Time |
|----|----|----|
|Option 1|".round($time1,6)."|
|Option 2|".round($time2,6)."|";

```

| Option | Execution Time |
|----|----|----|
|Option 1|2.156653|
|Option 2|2.099643|
