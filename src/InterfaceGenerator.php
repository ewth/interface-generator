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

    /** @var array $classes */
    protected $classes = [];
    /** @var array $classFiles */
    protected $classFiles = [];
    /** @var array $properties */
    protected $properties = [];
    /** @var array $functions */
    protected $functions = [];
    /** @var array $interfaces */
    protected $interfaces = [];
    /** @var string $outputDir */
    protected $outputDir = '';
    /** @var bool $overwriteFiles */
    protected $overwriteFiles = false;
    /** @var bool $useExplicitTypes */
    protected $useExplicitTypes = false;

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

        $this->writeInterfaces();

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
                    $this->classes[$key] = $matches[1];
                }
            }
        }

    }

    /**
     * Load phpdoc properties from all classes.
     */
    private function loadProperties()
    {
        foreach ($this->classes as $key => $value) {
            $properties             = $this->getProperties($this->classFiles[$key]);
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
    private function getProperties($classContent)
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
        $functionTemplate = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'FunctionTemplate.txt';
        if (!file_exists($functionTemplate)) {
            throw new \Exception('Function template could not be found.');
        }

        $functionTemplate = file_get_contents($functionTemplate);

        foreach ($this->properties as $key => $properties) {
            $functions = [];

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

                        $propertyVariable = strtolower($propertyName{0}) . substr($propertyName, 1);

                        $phpDoc = [
                            '/**',
                            ' * @return ' . ($propertyType ?? 'mixed'),
                            ' */'
                        ];

                        $function = str_replace('{{FUNCTION_NAME}}', 'get' . $propertyName, $functionTemplate);
                        $function = str_replace('{{ARGUMENTS}}', '', $function);
                        $function = str_replace('{{PHPDOC}}', implode("\n    ", $phpDoc), $function);

                        $functions[] = $function;

                        $phpDoc = [
                            '/**',
                            ' * @param ' . trim($propertyType . ' $' . $propertyVariable),
                            ' *',
                            ' * @return $this',
                            ' */'
                        ];

                        $function  = str_replace('{{FUNCTION_NAME}}', 'set' . $propertyName, $functionTemplate);
                        $arguments = '';
                        if ($this->useExplicitTypes) {
                            if ($propertyType && $propertyType != 'mixed') {
                                $arguments = $propertyType . ' ';
                            }
                        }
                        $arguments = trim($arguments . '$' . $propertyVariable);
                        $function = str_replace('{{ARGUMENTS}}', $arguments, $function);
                        $function = str_replace('{{PHPDOC}}', implode("\n    ", $phpDoc), $function);

                        $functions[] = $function;
                    }
                }
            }
            $this->functions[$key] = $functions;
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

        foreach ($this->functions as $key => $function) {
            $interface              = str_replace('{{INTERFACE_NAME}}', $this->classes[$key] . 'Interface', $interfaceTemplate);
            $interfaceFunctions     = implode("\n\n", $this->functions[$key]);
            $interface              = str_replace('{{FUNCTIONS}}', $interfaceFunctions, $interface);
            $this->interfaces[$key] = $interface;
        }
    }

    /**
     * Write interfaces to files.
     * @throws \Exception
     */
    private function writeInterfaces()
    {
        $outputDir = $this->outputDir . DIRECTORY_SEPARATOR;
        $outputDir = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $outputDir);

        foreach ($this->interfaces as $key => $interface) {
            $outputFile = $this->classes[$key] . 'Interface.php';
            if (file_exists($outputDir . $outputFile)) {
                if (!$this->overwriteFiles) {
                    continue;
                }
            }
            if (!empty($this->interfaces[$key])) {
                try {
                    file_put_contents($outputDir . $outputFile, $this->interfaces[$key]);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function getUseExplicitTypes()
    {
        return $this->useExplicitTypes;
    }

    /**
     * @param bool $useExplicitTypes
     *
     * @return $this
     */
    public function setUseExplicitTypes($useExplicitTypes)
    {
        $this->useExplicitTypes = $useExplicitTypes;

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
    private function getPublic($classContent)
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
    private function getPrivate($classContent)
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
    private function getProtected($classContent)
    {
        return [];
    }
}