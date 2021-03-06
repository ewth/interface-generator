<?php

include('src/InterfaceGenerator.php');

use Ewan\InterfaceGenerator;

$generator = new InterfaceGenerator();

// Setup generator
$generator->setOutputDir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'output')
          ->setOverwriteFiles(true)
          ->setArgumentTypes(true)
          ->setReturnTypes(true)
          ->setGenerateClasses(true);

// Loop through classes in example dir and load all into class
$files = glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'input' . DIRECTORY_SEPARATOR . '*.php');
foreach ($files as $file) {
    echo "Adding $file\n";
    $generator->addClass($file);
}

echo "Files added.\n";

echo "Generating output files...\n";
// Begin generating interfaces
$generator->generate();

echo "\nDone!\n";