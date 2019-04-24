<?php

namespace Ewan;

/**
 * Class InterfaceGenerator
 *
 *  This quickly generates a nice interface with getters and setters based on a class's properties.
 *
 * @package InterfaceGenerator
 */
class InterfaceGenerator
{

    /** @var array $classNames */
    protected $classNames = [];

    /** @var array $classFiles */
    protected $classFiles = [];

    /** @var array $properties */
    protected $properties = [];

    /** @var array $interfaceFunctions */
    protected $interfaceFunctions = [];

    /** @var array $classFunctions */
    protected $classFunctions = [];

    /** @var array $classProperties */
    protected $classProperties = [];

    /** @var array $interfaces */
    protected $interfaces = [];

    /** @var array $classes */
    protected $classes = [];

    /** @var string $outputDir */
    protected $outputDir = '';

    /** @var bool $overwriteFiles */
    protected $overwriteFiles = false;

    /** @var bool $generateClasses */
    protected $generateClasses = false;

    /** @var bool $argumentTypes */
    protected $argumentTypes = false;

    /** @var bool $returnTypes */
    protected $returnTypes = false;

    /**
     * @param string $class
     *
     * @throws \Exception
     */
    public function addClass($class)
    {
        $this->addFile($class);
    }

    /**
     * @param string $filePath
     *
     * @throws \Exception
     */
    private function addFile($filePath)
    {

        if (!$filePath || !file_exists($filePath)) {
            throw new \Exception("File could not be found: '{$filePath}'");
        }

        $this->classFiles[] = $filePath;
    }

    /**
     * Generate interfaces based on added classes. Optionally specify which items to include(true)/exclude(false).
     *
     * @param bool $public
     * @param bool $private
     * @param bool $protected
     * @param bool $docProperty
     *
     * @throws \Exception
     */
    public function generate($public = true, $private = true, $protected = true, $docProperty = true)
    {
        $this->loadFiles();

        if ($docProperty) {
            $this->loadProperties();
        }

        $this->createFunctions();

        $this->createInterfaces();

        if ($this->generateClasses) {
            $this->createClasses();
        }

        $this->writeFiles();

    }

    /**
     * Load all specified class files to parse their content.
     */
    private function loadFiles()
    {
        foreach ($this->classFiles as $key => $filePath) {
            if (file_exists($filePath)) {
                $fileContent            = file_get_contents($filePath);
                $fileContent            = str_replace("\r\n", "\n", $fileContent);
                $this->classFiles[$key] = $fileContent;
                // Coarse class detection
                if (preg_match('#\n[ ]*class ([^\{\n ]+)#i', $fileContent, $matches)) {
                    $this->classNames[$key] = $matches[1];
                }
            }
        }

    }

    /**
     * Load phpdoc properties from all classes.
     */
    private function loadProperties()
    {
        foreach ($this->classNames as $key => $value) {
            $properties             = $this->fetchProperties($this->classFiles[$key]);
            $this->properties[$key] = $properties;
        }
    }

    /**
     * Extract PHPDoc properties from the class content.
     *
     * @param string $classContent
     *
     * @return array
     */
    private function fetchProperties($classContent)
    {
        // @property string $Name        Name on the card
        $properties = [];
        /*
         * We want to match any of the following:
         * @property $name
         * @property type $name
         * @property $name Description
         * @property type $name Description
         * @property \Explicit\Class\Type $name
         * @property \Explicit\Class\Type $name $description
         * But we're only interested in type and name.
         */
        if (preg_match_all('#[ \n]@property[ ]+([a-zA-Z0-9\_\\\]+)?[ ]+?\$([a-zA-Z0-9\_]+)#', $classContent, $matches)) {
            foreach ($matches[0] as $key => $item) {
                $properties[] = [
                    'type' => $matches[1][$key],
                    'name' => $matches[2][$key]
                ];
            }
        }

        return $properties;
    }

    /**
     * Creates functions.
     * @throws \Exception
     */
    private function createFunctions()
    {
        $interfaceFunctionTemplate = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'InterfaceFunctionTemplate.txt';
        $classFunctionTemplate     = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'ClassFunctionTemplate.txt';
        if (!file_exists($interfaceFunctionTemplate) || !file_exists($classFunctionTemplate)) {
            throw new \Exception('Function template could not be found.');
        }

        $interfaceFunctionTemplate = file_get_contents($interfaceFunctionTemplate);
        $classFunctionTemplate     = file_get_contents($classFunctionTemplate);

        foreach ($this->properties as $key => $properties) {
            $interfaceFunctions = $classFunctions = $classProperties = [];

            foreach ($properties as $property) {
                $propertyName = $property['name'] ?? '';
                if ($propertyName) {

                    // Turn snake_case into camelCase
                    $propertyName = preg_replace_callback('#([^A-Za-z])([a-z])#', function ($item) {
                        return strtoupper($item[2]);
                    }, $propertyName);

                    // Make sure CAMELCase becomes camelCase
                    $propertyName = preg_replace_callback('#([A-Z])([A-Z]+)#', function ($item) {
                        return strtoupper($item[1]) . strtolower($item[2]);
                    }, $propertyName);

                    $propertyName = ucwords($propertyName);
                    $propertyName = preg_replace('#[^a-zA-Z0-9\_]#', '', $propertyName);
                    if ($propertyName) {
                        $propertyType = $property['type'] ?? '';

                        $returnType = '';
                        if ($this->returnTypes && $propertyType && $propertyType != 'mixed') {
                            $returnType = ": ${propertyType}";
                        }

                        $propertyVariable = strtolower($propertyName{0}) . substr($propertyName, 1);

                        $phpDoc = [
                            '/**',
                            ' * @return ' . ($propertyType ?? 'mixed'),
                            ' */'
                        ];

                        // todo: bit of code duplication going on here. Consider improving.

                        $interfaceFunction = str_replace('{{FUNCTION_NAME}}', "get${propertyName}", $interfaceFunctionTemplate);
                        $interfaceFunction = str_replace('{{ARGUMENTS}}', '', $interfaceFunction);
                        $interfaceFunction = str_replace('{{PHPDOC}}', implode("\n    ", $phpDoc), $interfaceFunction);
                        $interfaceFunction = str_replace('{{RETURN_TYPE}}', $returnType, $interfaceFunction);

                        $classFunction     = str_replace('{{FUNCTION_NAME}}', "get${propertyName}", $classFunctionTemplate);
                        $classFunction     = str_replace('{{ARGUMENTS}}', '', $classFunction);
                        $classFunction     = str_replace('{{PHPDOC}}', implode("\n    ", $phpDoc), $classFunction);
                        $classFunction     = str_replace('{{FUNCTION}}', "return \$this->${propertyVariable};", $classFunction);
                        $classFunction = str_replace('{{RETURN_TYPE}}', $returnType, $classFunction);

                        // Not strictly functions but makes sense to grab them here
                        $classProperties[] = [
                            'type' => $propertyType,
                            'name' => $propertyVariable
                        ];

                        $interfaceFunctions[] = $interfaceFunction;
                        $classFunctions[]     = $classFunction;

                        $phpDoc = [
                            '/**',
                            ' * @param ' . trim($propertyType . ' $' . $propertyVariable),
                            ' *',
                            ' * @return $this',
                            ' */'
                        ];

                        $arguments = '';
                        if ($this->argumentTypes && $propertyType && $propertyType != 'mixed') {
                            $arguments = $propertyType . ' ';
                        }

                        $returnType = '';
                        if ($this->returnTypes) {
                            $returnType = ': self';
                        }

                        $arguments = trim($arguments . '$' . $propertyVariable);

                        $interfaceFunction = str_replace('{{FUNCTION_NAME}}', "set${propertyName}", $interfaceFunctionTemplate);
                        $interfaceFunction = str_replace('{{ARGUMENTS}}', $arguments, $interfaceFunction);
                        $interfaceFunction = str_replace('{{PHPDOC}}', implode("\n    ", $phpDoc), $interfaceFunction);
                        $interfaceFunction = str_replace('{{RETURN_TYPE}}', $returnType, $interfaceFunction);


                        $classFunction = str_replace('{{FUNCTION_NAME}}', "set${propertyName}", $classFunctionTemplate);
                        $classFunction = str_replace('{{ARGUMENTS}}', $arguments, $classFunction);
                        $classFunction = str_replace('{{PHPDOC}}', implode("\n    ", $phpDoc), $classFunction);
                        $classFunction = str_replace('{{RETURN_TYPE}}', $returnType ? ': ' . $this->classNames[$key] . 'Interface' : '', $classFunction);

                        $function      = [
                            '$this->' . $propertyVariable . ' = $' . $propertyVariable . ';',
                            '',
                            'return $this;'
                        ];
                        $classFunction = str_replace('{{FUNCTION}}', implode("\n        ", $function), $classFunction);

                        $interfaceFunctions[] = $interfaceFunction;
                        $classFunctions[]     = $classFunction;
                    }
                }
            }
            $this->interfaceFunctions[$key] = $interfaceFunctions;
            $this->classFunctions[$key]     = $classFunctions;
            $this->classProperties[$key]    = $classProperties;
        }
    }

    /**
     * Creates interfaces.
     * @throws \Exception
     */
    private function createInterfaces()
    {
        $interfaceTemplate = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'InterfaceTemplate.txt';
        if (!file_exists($interfaceTemplate)) {
            throw new \Exception('Interface template could not be found.');
        }

        $interfaceTemplate = file_get_contents($interfaceTemplate);

        foreach ($this->interfaceFunctions as $key => $function) {
            $interface              = str_replace('{{INTERFACE_NAME}}', $this->classNames[$key] . 'Interface', $interfaceTemplate);
            $interface              = str_replace('{{FUNCTIONS}}', implode("\n\n", $this->interfaceFunctions[$key]), $interface);
            $this->interfaces[$key] = $interface;
        }
    }

    /**
     * Creates classes.
     * @throws \Exception
     */
    private function createClasses()
    {
        $classTemplate = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'ClassTemplate.txt';
        if (!file_exists($classTemplate)) {
            throw new \Exception('Interface template could not be found.');
        }

        $classTemplate = file_get_contents($classTemplate);

        foreach ($this->classFunctions as $key => $function) {

            $classProperties       = $this->classProperties[$key];
            $classPropertiesOutput = [];
            foreach ($classProperties as $classProperty) {
                $propertyType            = $classProperty['type'] ?? '';
                $propertyName            = $classProperty['name'] ?? '';
                $classPropertiesOutput[] = '';

                if ($propertyType && $propertyType != 'mixed') {
                    $classPropertiesOutput[] = "/** @var ${propertyType} \$${propertyName} */";
                }
                $classPropertiesOutput[] = "protected \$${propertyName};";

            }

            $class               = str_replace('{{CLASS_NAME}}', $this->classNames[$key], $classTemplate);
            $class               = str_replace('{{INTERFACE_NAME}}', $this->classNames[$key] . 'Interface', $class);
            $class               = str_replace('{{FUNCTIONS}}', implode("\n\n", $this->classFunctions[$key]), $class);
            $class               = str_replace('{{PROPERTIES}}', implode("\n    ", $classPropertiesOutput), $class);
            $this->classes[$key] = $class;
        }
    }

    /**
     * Write all files.
     * @throws \Exception
     */
    private function writeFiles()
    {
        $outputDir = $this->outputDir . DIRECTORY_SEPARATOR;
        $outputDir = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $outputDir);

        foreach ($this->interfaces as $key => $interface) {
            $interfaceOutputFile = $outputDir . $this->classNames[$key] . 'Interface.php';
            $classOutputFile     = $outputDir . $this->classNames[$key] . '.php';
            $writeInterface      = $writeClass = true;

            if (!$this->overwriteFiles) {
                if (file_exists($interfaceOutputFile)) {
                    $writeInterface = false;
                }
                if (file_exists($classOutputFile)) {
                    $writeClass = false;
                }
            }

            if ($writeInterface && !empty($this->interfaces[$key])) {
                file_put_contents($interfaceOutputFile, $this->interfaces[$key]);

            }

            if ($this->generateClasses && $writeClass && !empty($this->classes[$key])) {
                file_put_contents($classOutputFile, $this->classes[$key]);
            }
        }
    }

    /**
     * Create shell classes implementing interfaces.
     */

    /**
     * @return bool
     */
    public function getArgumentTypes()
    {
        return $this->argumentTypes;
    }

    /**
     * @param bool $argumentTypes
     *
     * @return $this
     */
    public function setArgumentTypes($argumentTypes)
    {
        $this->argumentTypes = $argumentTypes;

        return $this;
    }

    /**
     * @return bool
     */
    public function getGenerateClasses()
    {
        return $this->generateClasses;
    }

    /**
     * @param bool $generateClasses
     */
    public function setGenerateClasses($generateClasses)
    {
        $this->generateClasses = $generateClasses;
    }

    /**
     * @return string
     */
    public function getOutputDir()
    {
        return $this->outputDir;
    }

    /**
     * @param string $outputDir
     *
     * @return $this
     */
    public function setOutputDir($outputDir)
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOverwriteFiles()
    {
        return $this->overwriteFiles;
    }

    /**
     * @param bool $overwriteFiles
     *
     * @return $this
     */
    public function setOverwriteFiles($overwriteFiles)
    {
        $this->overwriteFiles = $overwriteFiles;

        return $this;
    }

    /**
     * @return bool
     */
    public function getReturnTypes()
    {
        return $this->returnTypes;
    }

    /**
     * @param bool $returnTypes
     *
     * @return $this
     */
    public function setReturnTypes($returnTypes)
    {
        $this->returnTypes = $returnTypes;

        return $this;
    }

    /**
     * todo: implement
     *
     * Load public properties from all classes.
     *
     * @return array
     */
    private function loadPublic()
    {
        return [];
    }

    /**
     * todo: implement
     *
     * Load private properties from all classes.
     *
     * @return array
     */
    private function loadPrivate()
    {
        return [];
    }

    /**
     * todo: implement
     *
     * Load phpdoc properties from all classes.
     *
     * @return array
     */
    private function loadProtected()
    {
        return [];
    }

    /**
     * todo: implement
     *
     * @param string $classContent
     *
     * @return array
     */
    private function fetchPublic($classContent)
    {
        return [];
    }

    /**
     * todo: implement
     *
     * @param string $classContent
     *
     * @return array
     */
    private function fetchPrivate($classContent)
    {
        return [];
    }

    /**
     * todo: implement
     *
     * @param string $classContent
     *
     * @return array
     */
    private function fetchProtected($classContent)
    {
        return [];
    }
}