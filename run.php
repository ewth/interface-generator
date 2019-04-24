<?php

include('src/InterfaceGenerator.php');

use Ewan\InterfaceGenerator;

$generator = new InterfaceGenerator();

// Setup generator
$generator->setOutputDir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'output')
          ->setOverwriteFiles(false)
          ->setUseExplicitTypes(true);

// Loop through classes in example dir and load all into class
$files = glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'example' . DIRECTORY_SEPARATOR . '*.php');
foreach ($files as $file) {
    echo "Adding $file\n";
    $generator->addClass($file);
}

// Begin generating interfaces
$generator->generate();