<?php
// Register autoloader
require "./vendor/autoload.php";
\ShapeFile\ShapeFileAutoloader::register();

// Import classes
use \ShapeFile\ShapeFile;
use \ShapeFile\ShapeFileException;

try {
    // Open shapefile
    $ShapeFile = new ShapeFile('/home/chalom/Downloads/diva/dALA/ALA_adm0.shp');
    
    // Read all the records
    while ($record = $ShapeFile->getRecord(ShapeFile::GEOMETRY_BOTH)) {
        if ($record['dbf']['_deleted']) continue;
        // Geometry
        print_r($record['shp']);
        // DBF Data
        print_r($record['dbf']);
    }
    
} catch (ShapeFileException $e) {
    // Print detailed error information
    exit('Error '.$e->getCode().' ('.$e->getErrorType().'): '.$e->getMessage());
}


