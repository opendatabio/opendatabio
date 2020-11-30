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
