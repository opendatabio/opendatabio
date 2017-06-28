<?php
// Register autoloader
require "./vendor/autoload.php";
\ShapeFile\ShapeFileAutoloader::register();

// Import classes
use \ShapeFile\ShapeFile;
use \ShapeFile\ShapeFileException;

try {
    // Open shapefile
    $ShapeFile = new ShapeFile('/home/chalom/Downloads/BRA_adm2');
    
    // Read all the records
    while ($record = $ShapeFile->getRecord(ShapeFile::GEOMETRY_WKT)) {
	    if( $ShapeFile->getCurrentRecord() != 1000) continue;
        if ($record['dbf']['_deleted']) continue;
        // Geometry
#	print_r(array_keys($record['shp']));
#	echo (($record['shp']['numparts']));
#	foreach($record['shp']['parts'] as $part)
#		if($part['numrings'] > 1) echo $part['numrings'];
#	print_r(($record['shp']['parts'][0]));
#	print_r(($record['shp']['parts'][1]));
        print_r($record['shp']);
        // DBF Data
	echo "\n";
       print_r($record['dbf']);
    }
    
} catch (ShapeFileException $e) {
    // Print detailed error information
    exit('Error '.$e->getCode().' ('.$e->getErrorType().'): '.$e->getMessage());
}


