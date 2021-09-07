<?php

return [

   "datasetreferences" => [
     'filename' =>   "dataset_bibreference.png",
     'models' => [
      App\Models\Dataset::class,
      App\Models\BibReference::class,
    ],
    'rankdir' => 'RL',
    'ranksep' => 2,
    'nodesep' => 2,
  ],

   "datasets" => [
    'filename' =>   "dataset_model.png",
    'models' => [
      App\Models\Dataset::class,
      App\Models\Measurement::class,
      App\Models\User::class,
      App\Models\Project::class,
    ],
    'rankdir' => 'TB',
    'ranksep' => 0.5,
    'nodesep' => 2,
  ],


   "projects" => [
     'filename' =>   "project_model.png",
      'models' => [
        App\Models\Project::class,
        App\Models\Dataset::class,
        App\Models\User::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 3,
      'nodesep' => 1,
    ],

    "users" => [
      'filename' =>   "user_model.png",
      'models' => [
        App\Models\Person::class,
        App\Models\User::class,
        App\Models\Dataset::class,
        App\Models\Project::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 3,
      'nodesep' => 1,
    ],

    "userjobs" => [
      'filename' =>   "user_userjob.png",
      'models' => [
        App\Models\User::class,
        App\Models\UserJob::class,
      ],
      'rankdir' => 'RL',
      'ranksep' => 2,
      'nodesep' => 2,
    ],

    "biocollections" => [
      'filename' =>   "biocollection_model.png",
      "models" => [
        App\Models\Biocollection::class,
        App\Models\Voucher::class,
        App\Models\Person::class,
        App\Models\Identification::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 2,
      'nodesep' => 1,
    ],

    "coreobjects" => [
      'filename' =>   "model_coreobjects.png",
      'use_db_schema' => false,
      'rankdir' => 'RL',
      'ranksep' => 1,
      'nodesep' => 0.6,
      "models" => [
        App\Models\Voucher::class,
        App\Models\Location::class,
        App\Models\Taxon::class,
        App\Models\Individual::class,
        App\Models\Measurement::class,
        App\Models\Identification::class,
      ]
    ],
    "locations" => [
      'filename' =>   "location_model.png",
      'use_db_schema' => false,
      "models" => [
        App\Models\Location::class,
        App\Models\Individual::class,
        App\Models\Measurement::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 2,
      'nodesep' => 1.5,
    ],

    "individuals" => [
      'filename' =>   "individual_model.png",
      'use_db_schema' => false,
      "models" => [
        App\Models\Individual::class,
        App\Models\Voucher::class,
        //App\Models\Location::class,
        App\Models\IndividualLocation::class,
        App\Models\Dataset::class,
        App\Models\Identification::class,
        App\Models\Taxon::class,
        App\Models\Collector::class,
        App\Models\Person::class,
      ],
      'rankdir' => 'RL',
      'ranksep' => 1,
      'nodesep' => 0.6,
    ],

    "taxons" => [
        'filename' =>   "taxon_model.png",
      "models" => [
        App\Models\Taxon::class,
        App\Models\TaxonExternal::class,
        App\Models\Person::class,
        App\Models\Identification::class,
        App\Models\BibReference::class
      ],
      'rankdir' => 'LR',
      'ranksep' => 2,
      'nodesep' => 0.5,
    ],

    "vouchers" => [
      'filename' =>   "voucher_model.png",
      "models" => [
        App\Models\Voucher::class,
        App\Models\Individual::class,
        App\Models\Biocollection::class,
        App\Models\Collector::class,
        App\Models\Dataset::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 3,
      'nodesep' => 1,
    ],

    "persons" => [
      'filename' =>   "person_model.png",
      "models" => [
        App\Models\Person::class,
        App\Models\Collector::class,
        App\Models\Voucher::class,
        App\Models\Measurement::class,
        App\Models\Identification::class,
        App\Models\Taxon::class,
        App\Models\User::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 1.5,
      'nodesep' => 0.5,
    ],

    "personstable" => [
      'filename' =>   "persons_table.png",
      "models" => [
        App\Models\Person::class,
      ],
      'rankdir' => 'LR',
      'ranksep' => 2,
      'nodesep' => 1,
    ],

    "bibreferences" => [
      'filename' =>   "bibreference_model.png",
      "models" => [
          App\Models\Dataset::class,
          App\Models\BibReference::class,
          App\Models\Taxon::class,
          App\Models\Measurement::class,
          App\Models\ODBTrait::class,
        ],
        'rankdir' => 'LR',
        'ranksep' => 2.2,
        'nodesep' => 0.4,
      ],


  "identifications" => [
    'filename' =>   "identification_model.png",
    "models" => [
      App\Models\Individual::class,
      App\Models\Identification::class,
      App\Models\Person::class,
      App\Models\Taxon::class,
      App\Models\Biocollection::class,
    ],
    'rankdir' => 'RL',
    'ranksep' => 2,
    'nodesep' => 1,
  ],

  "media" => [
    'filename' =>   "media_model.png",
    "models" => [
      App\Models\Media::class,
      App\Models\Voucher::class,
      App\Models\Location::class,
      App\Models\Collector::class,
      App\Models\Taxon::class,
      App\Models\Individual::class,
      App\Models\Tag::class,
      App\Models\UserTranslation::class,
      App\Models\Dataset::class,
    ],
    'rankdir' => 'LR',
    'ranksep' => 2,
    'nodesep' => 0.6,
  ],

  "tags" => [
    'filename' =>   "tag_model.png",
    "models" => [
      App\Models\Tag::class,
      App\Models\Project::class,
      App\Models\Dataset::class,
      App\Models\Media::class,
      App\Models\UserTranslation::class,
    ],
    'rankdir' => 'LR',
    'ranksep' => 2,
    'nodesep' => 0.6,
  ],

  "measurements" => [
    'filename' =>   "measurement_model.png",
    "models" => [
      App\Models\ODBTrait::class,
      App\Models\TraitCategory::class,
      App\Models\Measurement::class,
      App\Models\MeasurementCategory::class,
      App\Models\Person::class,
      App\Models\Dataset::class,
      //App\Models\UserTranslation::class,
    ],
    'rankdir' => 'LR',
    'ranksep' => 2,
    'nodesep' => 0.5,
  ],

  "traits" => [
    'filename' =>   "trait_model.png",
    "models" => [
      App\Models\ODBTrait::class,
      App\Models\TraitCategory::class,
      App\Models\TraitObject::class,
      App\Models\UserTranslation::class,
      App\Models\Language::class,
      App\Models\BibReference::class,
    ],
    'rankdir' => 'LR',
    'ranksep' => 2,
    'nodesep' => 0.5,
  ],

  "usertranslations" => [
    'filename' =>   "user_translations.png",
    "models" => [
      App\Models\UserTranslation::class,
      App\Models\Language::class,
      App\Models\Media::class,
      App\Models\ODBTrait::class,
      App\Models\TraitCategory::class,
      App\Models\Tag::class,
    ],
    'rankdir' => 'RL',
    'ranksep' => 2,
    'nodesep' => 1,
  ],

];
