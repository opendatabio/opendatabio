<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */
/* fields for get APIs */
return [
  'taxons' => [
    'all' =>  ['id','senior_id', 'parent_id', 'author_id', 'scientificName', 'taxonRank', 'scientificNameAuthorship', 'namePublishedIn', 'parentName',
    'family','higherClassification', 'taxonRemarks','taxonomicStatus','acceptedNameUsage','acceptedNameUsageID','parentNameUsage', 'ScientificNameID','basisOfRecord'],
    'simple' =>  ['id','parent_id', 'author_id', 'scientificName', 'taxonRank', 'scientificNameAuthorship', 'namePublishedIn', 'parentName',
    'family','taxonRemarks','taxonomicStatus','ScientificNameID','basisOfRecord'],
  ],
  'locations' => [
    'all' => ['id','basisOfRecord','locationName','adm_level','levelName','x','y','startx','starty','distance','parent_id','parentName','higherGeography','footprintWKT','locationRemarks','decimalLatitude','decimalLongitude','georeferenceRemarks','geodeticDatum'],
    'simple' => ['id','basisOfRecord','locationName','adm_level','levelName','x','y','startx','starty','distance','parent_id','parentName','higherGeography','footprintWKT','locationRemarks','decimalLatitude','decimalLongitude','georeferenceRemarks','geodeticDatum'],
  ],
  'individuals' => [
    'all' => ['id','basisOfRecord','organismID','recordedByMain','recordNumber','recordedDate','recordedBy','scientificName','genus','family','identificationQualifier','identifiedBy','dateIdentified','identificationRemarks',
    'locationName','higherGeography','decimalLatitude','decimalLongitude','georeferenceRemarks','locationParentName','x','y','gx','gy','angle','distance','organismRemarks','associatedMedia','datasetName','accessRights','bibliographicCitation','license'],
    'simple' => ['id','basisOfRecord','organismID','recordedByMain','recordNumber','recordedDate','recordedBy','scientificName','genus','family','identificationQualifier','identifiedBy','dateIdentified','identificationRemarks',
    'locationName','higherGeography','decimalLatitude','decimalLongitude','georeferenceRemarks','locationParentName','x','y','gx','gy','angle','distance','organismRemarks','associatedMedia','datasetName','accessRights','bibliographicCitation','license'],
  ],
  'vouchers' => [
    'all' => ['id',"individual_id",'basisOfRecord','occurrenceID', 'organismID','collectionCode','catalogNumber','typeStatus','recordedByMain','recordNumber','recordedDate','recordedBy','scientificName','genus','family','identificationQualifier','identifiedBy','dateIdentified','identificationRemarks', 'locationName','higherGeography','decimalLatitude','decimalLongitude','georeferenceRemarks','occurrenceRemarks','associatedMedia','datasetName','accessRights','bibliographicCitation','license'],
    'simple' => ['id',"individual_id",'basisOfRecord','occurrenceID', 'organismID','collectionCode','catalogNumber','typeStatus','recordedByMain','recordNumber','recordedDate','recordedBy','scientificName','genus','family','identificationQualifier','identifiedBy','dateIdentified','identificationRemarks', 'locationName','higherGeography','decimalLatitude','decimalLongitude','georeferenceRemarks','occurrenceRemarks','associatedMedia','datasetName','accessRights','bibliographicCitation','license'],
  ],
  'measurements' => [
    'all' => ['id', 'basisOfRecord','measured_type', 'measured_id','measurementType', 'measurementValue','measurementUnit','measurementDeterminedDate', 'measurementDeterminedBy','measurementRemarks','resourceRelationship','resourceRelationshipID','relationshipOfResource','scientificName','family','datasetName','accessRights','bibliographicCitation','measurementMethod','license'],
    'simple' => ['id','basisOfRecord', 'measured_type', 'measured_id','measurementType', 'measurementValue','measurementUnit','measurementDeterminedDate','scientificName','datasetName','accessRights','bibliographicCitation','license'],
  ],
  'odbtraits' => [
    'all' => ['id', 'type', 'typename','export_name','measurementType','measurementUnit', 'range_min', 'range_max','link_type','value_length','name','description','objects','measurementMethod','categories'],
    'simple' => ['id', 'type', 'typename','export_name','unit', 'range_min', 'range_max','link_type','value_length','name','description','objects','measurementType','measurementMethod','categories'],
    'exceptcategories' => ['id', 'type', 'typename','export_name','unit', 'range_min', 'range_max','link_type','value_length','name','description','objects','measurementType','measurementMethod'],
  ],
  'individuallocations' => [
    'all' => ['id','basisOfRecord','occurrenceID','organismID','recordedDate', 'locationName','higherGeography','decimalLatitude','decimalLongitude','georeferenceRemarks','x','y','angle','distance','minimumElevation','occurrenceRemarks','organismRemarks','datasetName','accessRights','bibliographicCitation','scientificName','family','license'],
    'simple' => ['id','basisOfRecord','occurrenceID','organismID','recordedDate', 'locationName','decimalLatitude','decimalLongitude','x','y','angle','distance','minimumElevation','occurrenceRemarks','scientificName','accessRights','bibliographicCitation','license'],
  ],
  'bibreferences' => [
    'simple' => ['id', 'bibkey', 'year', 'author','title','doi','url','bibtex'],
    'all' => ['id', 'bibkey', 'year', 'author','title','doi','url','bibtex'],
  ],
  'biocollections' => [
    'all' => ['id', 'acronym', 'name', 'irn'],
    'simple' => ['id', 'acronym', 'name', 'irn'],
  ],
  'persons' => [
    'simple' => ['id', 'full_name', 'abbreviation', 'email', 'institution','notes'],
    'all' => ['id', 'full_name', 'abbreviation', 'email', 'institution','notes'],
  ],
  'media' => [
    'all' => ['id', 'model_type', 'model_id','basisOfRecord','recordedBy','recordedDate', 'dwcType', 'resourceRelationship', 'resourceRelationshipID', 'relationshipOfResource', 'scientificName', 'family', 'datasetName', 'accessRights', 'bibliographicCitation','license','file_name','file_url'],
    'simple' => ['id', 'model_type', 'model_id','basisOfRecord','recordedBy','recordedDate', 'dwcType', 'resourceRelationship', 'resourceRelationshipID', 'relationshipOfResource', 'scientificName', 'family', 'datasetName', 'accessRights', 'bibliographicCitation','license','file_name'],
  ],
];
