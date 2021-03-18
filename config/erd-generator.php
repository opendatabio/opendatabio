<?php

return [

    /*
     * All models in these directories will be scanned for ER diagram generation.
     * By default, the `app` directory will be scanned recursively for models.
     */
    'directories' => [
        base_path('app'),
    ],

    /*
     * If you want to ignore complete models or certain relations of a specific model,
     * you can specify them here.
     * To ignore a model completely, just add the fully qualified classname.
     * To ignore only a certain relation of a model, enter the classname as the key
     * and an array of relation names to ignore.
     */
    'ignore' => [
        //User::class,
        //Dataset::class => [
        //     'dataset_user'
        //]
    ],

    /*
     * If you want to see only specific models, specify them here using fully qualified
     * classnames.
     *
     * Note: that if this array is filled, the 'ignore' array will not be used.
    */
    'whitelist' => [
        /* dataset_bibreference.png */
          //App\Dataset::class,
          //App\BibReference::class,

        /* dataset_model.png */
          //App\Dataset::class,
          //App\Measurement::class,
          //App\User::class,

        /* project_model.png */
          //App\Project::class,
          //App\Voucher::class,
          //App\Individual::class,
          //App\User::class,

        /* user_model.png */
          //App\Person::class,
          //App\User::class,
          //App\Dataset::class,
          //App\Project::class,

        /* user_userjob.png */
          //App\User::class,
          //App\UserJob::class,

        /* biocollection_model.png */
          //App\Biocollection::class,
          //App\Voucher::class,
          //App\Person::class,

        /* model_coreobjects.png
          App\Voucher::class,
          App\Location::class,
          App\Taxon::class,
          App\Individual::class,
          App\Measurement::class,
          App\Identification::class,
          */
        /* location_model.png
          App\Location::class,
          App\Individual::class,
          App\Measurement::class,
          */
        /* Individual_model.png
        App\Individual::class,
        App\Voucher::class,
        //App\Location::class,
        App\Project::class,
        App\Identification::class,
        App\Taxon::class,
        App\Collector::class,
        App\Person::class,
        */

        /* taxon_model.png
        App\Taxon::class,
        App\TaxonExternal::class,
        App\Person::class,
        App\Identification::class,
        App\BibReference::class
        */

        /* voucher_model.png
        App\Individual::class,
        App\Collector::class,
        App\Person::class,
        App\Biocollection::class,
        App\Project::class,
        */

        /* persons_model.png */
        //App\Person::class,
        //App\Collector::class,
        //App\Voucher::class,
        //App\Measurement::class,
        //App\Identification::class,
        //App\Taxon::class,
        //App\User::class,

        /* persons_table.png */
        App\Person::class,
        App\Collector::class,
        App\Biocollection::class,
        App\Identification::class,
        App\Measurement::class,
        App\Taxon::class,
        App\User::class,

        /* bibreferences_model.png
        App\Dataset::class,
        App\BibReference::class,
        App\Taxon::class,
        App\Measurement::class,
        App\ODBTrait::class,
        */

        /* identification_model
        //App\Voucher::class,
        App\Individual::class,
        App\Identification::class,
        App\Person::class,
        App\Taxon::class,
        App\Biocollection::class,
        */

        /* persons_table.png */
        //App\TraitCategory::class,
        //App\Identification::class,
        //App\ODBTrait::class,

        /* picture_model.png */
        //App\Voucher::class,
        //App\Location::class,
        //App\Collector::class,
        //App\Taxon::class,
        //App\Individual::class,
        //App\Picture::class,
        //App\Tag::class,
        //App\UserTranslation::class,

        /* tag_model.png */
        //App\Tag::class,
        //App\Project::class,
        //App\Dataset::class,
        //App\Picture::class,
        //App\UserTranslation::class,

        /* measurement_model.png */
        //App\ODBTrait::class,
        //App\TraitCategory::class,
        //App\Measurement::class,
        //App\MeasurementCategory::class,
        //App\Person::class,
        //App\Dataset::class,
        //App\UserTranslation::class,

        /* trait_model.png
        App\ODBTrait::class,
        App\TraitCategory::class,
        App\TraitObject::class,
        App\UserTranslation::class,
        App\Language::class,
        App\BibReference::class,
        */


        /* user translations */
        //App\UserTranslation::class,
        //App\Language::class,
        //App\Picture::class,
        //App\ODBTrait::class,
        //App\TraitCategory::class,
        //App\Tag::class,
    ],

    /*
     * If true, all directories specified will be scanned recursively for models.
     * Set this to false if you prefer to explicitly define each directory that should
     * be scanned for models.
     */
    'recursive' => false,

    /*
     * The generator will automatically try to look up the model specific columns
     * and add them to the generated output. If you do not wish to use this
     * feature, you can disable it here.
     */

     /* use false for  model_coreobjects.png */
     /* also for location_model.png and Individual_model.png, because Individual and location for some reason not working */
     /* // TODO: understand why Individual and location don't show table columns, others do and there is no obvious differences among models */
     /* also for the person_model.png */
     /* also for the picture_model.png */
     /* also for the tag_model.png */
     /* also for the identification_model.png */
    'use_db_schema' => true,

    /*
     * This setting toggles weather the column types (VARCHAR, INT, TEXT, etc.)
     * should be visible on the generated diagram. This option requires
     * 'use_db_schema' to be set to true.
     */
    'use_column_types' => true,

    /*
     * These colors will be used in the table representation for each entity in
     * your graph.
     */
    'table' => [
        'header_background_color' => '#D5EDF6',
        'header_font_color' => '#333333',
        'header_font_size' => 12,
        'row_background_color' => '#F0F0F0',
        'row_font_color' => '#333333',
        'row_font_size' => 11,
    ],

    /*
     * Here you can define all the available Graphviz attributes that should be applied to your graph,
     * to its nodes and to the edge (the connection between the nodes). Depending on the size of
     * your diagram, different settings might produce better looking results for you.
     *
     * See http://www.graphviz.org/doc/info/attrs.html#d:label for a full list of attributes.
     */
    'graph' => [
        'style' => 'filled',
        'bgcolor' => '#FFFFFF',
        'labelloc' => 't',
        'labelfloat' =>  true,
        'concentrate' => false,
        'splines' => 'spline',
        'overlap' => false,


        /* for dataset_model.png, project_model.png*/
        //'rankdir' => 'TB',
        //'ranksep' => 0.5,
        //'nodesep' => 2,


        /* model_coreobjects.png*/
        //'rankdir' => 'RL',
        //'ranksep' => 1,
        //'nodesep' => 0.6,

        /* location_model.png Individual_model.png*/
        //'rankdir' => 'RL',
        //'ranksep' => 1,
        //'nodesep' => 0.6,


        /* taxon_model.png trait_model.png and bibreference person */
        'rankdir' => 'LR',
        'ranksep' => 2,
        'nodesep' => 0.5,


        /* for identification_model
        'rankdir' => 'RL',
        'ranksep' => 2,
        'nodesep' => 1,
        */

        /*dataset_bibreference.png users_model.png user_userjob.png  biocollection_model*/
        //'rankdir' => 'RL',
        //'ranksep' => 2,
        //'nodesep' => 2,


        'esep' => false,
        'rotate' => 0,
        'fontname' => 'Helvetica Neue',
    ],

    'node' => [
        'margin' => 0,
        'shape' => 'rectangle',
        'fontname' => 'Helvetica Neue',
        'fontsize' => 11,
    ],

    'edge' => [
        'color' => '#003049',
        'fontcolor' =>  '#003049',
        'penwidth' => 1.5,
        'fontname' => 'Helvetica Neue',
        'fontsize' => 12,
    ],

    'relations' => [
        'HasOne' => [
            'dir' => 'both',
            'color' => '#FFCC00',
            'arrowhead' => 'tee',
            'arrowtail' => 'none',
        ],
        'BelongsTo' => [
            'dir' => 'both',
            'color' =>  '#7B0099',
            'fontcolor' =>  '#7B0099',
            'arrowhead' => 'normal',
            'arrowtail' => 'dot',
        ],
        'BelongsToMany' => [
            'dir' => 'both',
            'color' =>  '#FB9902',
            'fontcolor' =>  '#FB9902',
            'arrowhead' => 'crow',
            'arrowtail' => 'crow',
        ],
        'HasMany' => [
            'dir' => 'both',
            'color' => '#4285F4',
            'fontcolor' => '#4285F4',
            'arrowhead' => 'crow',
            'arrowtail' => 'dot',
        ],
        'MorphMany' => [
            'dir' => 'both',
            'color' => '#EA4335',
            'fontcolor' => '#EA4335',
            'arrowhead' => 'crow',
            'arrowtail' => 'dot',
        ],
        'MorphTo' => [
            'dir' => 'both',
            'color' => '#EA4335',
            'fontcolor' => '#EA4335',
            'arrowhead' => 'normal',
            'arrowtail' => 'dot',
            'style' => 'dotted',
        ],

        'HasManyThrough' => [
            'dir' => 'both',
            'color' => '#A4C639',
            'fontcolor' => '#A4C639',
            'arrowhead' => 'crow',
            'arrowtail' => 'dot',
            'style' => 'dashed'
        ],
        'HasOneThrough' => [
            'dir' => 'both',
            'color' => '#FFCC00',
            'fontcolor' => '#FFCC00',
            'arrowhead' => 'normal',
            'arrowtail' => 'dot',
            'style' => 'dashed'
        ],
    ]

];
