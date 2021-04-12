<?php

return [

    /*
     * All models in these directories will be scanned for ER diagram generation.
     * By default, the `app` directory will be scanned recursively for models.
     */
    'directories' => [
        base_path('app/Models'),
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
        /* model_coreobjects.png */
          App\Models\Voucher::class,
          App\Models\Location::class,
          App\Models\Taxon::class,
          App\Models\Individual::class,
          App\Models\Measurement::class,
          App\Models\Identification::class,
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

        'rankdir' => 'TB',
        'ranksep' => 1,
        'nodesep' => 1,

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
