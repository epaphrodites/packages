<?php

/**
 * EpaphroditesConfigReader - YAML Configuration Reader
 * 
 * Class dedicated solely to reading YAML configuration files
 * for the Epaphrodites framework. No save or modification functionality.
 */

namespace Epaphrodites\Packages\config;

use Exception;

class EpaphroditesConfigReader
{
    private $config = [];
    private $yamlFile;

    public function __construct($yamlFilePath)
    {
        $this->yamlFile = $yamlFilePath;
        $this->loadConfig();
    }

    /**
     * Load and parse the YAML file
     */
    private function loadConfig()
    {
        if (!file_exists($this->yamlFile)) {
            throw new Exception("YAML file does not exist: " . $this->yamlFile);
        }

        $content = file_get_contents($this->yamlFile);
        if ($content === false) {
            throw new Exception("Unable to read file: " . $this->yamlFile);
        }

        $this->config = $this->parseYaml($content);
    }

    /**
     * Parse YAML content into pure PHP
     */
    private function parseYaml($content)
    {
        $lines = explode("\n", $content);
        $result = [];
        $stack = [&$result];
        $levels = [0];
        $listContext = [false]; // To track if we're in a list context

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (empty($line) || $line[0] === '#') {
                continue;
            }

            $indent = 0;
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                if ($line[$i] === ' ') {
                    $indent++;
                } elseif ($line[$i] === "\t") {
                    $indent += 2;
                } else {
                    break;
                }
            }

            $line = trim($line);
            $level = intval($indent / 2);

            // Adjust stack according to indentation level
            while (count($levels) > $level + 1) {
                array_pop($stack);
                array_pop($levels);
                array_pop($listContext);
            }

            // Process list elements
            if (strpos($line, '- ') === 0) {
                $item = trim(substr($line, 2));
                $current = &$stack[count($stack) - 1];
                
                // If list element contains a key-value pair
                if (strpos($item, ':') !== false) {
                    list($key, $value) = $this->splitKeyValue($item);
                    $value = $this->convertValue($value);
                    
                    // Ensure current is an array
                    if (!is_array($current)) {
                        $current = [];
                    }
                    
                    // Add key-value directly (not as list element)
                    $current[$key] = $value;
                    
                    // Mark that we're in a list context with key-value
                    $listContext[count($listContext) - 1] = true;
                } else {
                    // Simple list element
                    if (!is_array($current)) {
                        $current = [];
                    }
                    $current[] = $this->convertValue($item);
                }
                continue;
            }

            // Process normal key-value pairs
            if (strpos($line, ':') !== false) {
                list($key, $value) = $this->splitKeyValue($line);
                $value = trim($value);

                $current = &$stack[count($stack) - 1];
                
                if (empty($value)) {
                    // Key without value, create new level
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $levels[] = $level + 1;
                    $listContext[] = false;
                } else {
                    // Key with value
                    $current[$key] = $this->convertValue($value);
                }
            }
        }

        return $result;
    }

    /**
     * Split a key:value line
     */
    private function splitKeyValue($line)
    {
        $pos = strpos($line, ':');
        if ($pos === false) {
            return [$line, ''];
        }
        
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        
        return [$key, $value];
    }

    /**
     * Convert a value to appropriate PHP type
     */
    private function convertValue($value)
    {
        $value = trim($value);
        
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null' || $value === '~' || $value === '') return null;
        
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? floatval($value) : intval($value);
        }
        
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            return substr($value, 1, -1);
        }
        
        return $value;
    }

    /**
     * Get the complete configuration array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get a configuration value by dot notation path
     */
    public function get($path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $this->config;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }
        
        return $current;
    }

    /**
     * Check if a configuration path exists
     */
    public function exists($path)
    {
        $keys = explode('.', $path);
        $current = $this->config;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return false;
            }
            $current = $current[$key];
        }
        
        return true;
    }

    /**
     * Get the framework version
     */
    public function getVersion()
    {
        return $this->get('version');
    }

    /**
     * Get the package name
     */
    public function getPackage()
    {
        return $this->get('package');
    }

    /**
     * Check if an update type is enabled
     */
    public function isUpdateTypeEnabled($type)
    {
        return $this->get("update.type.$type", false);
    }

    /**
     * Get update targets for a section
     */
    public function getUpdateTargets($section = null)
    {
        if ($section) {
            $sectionData = $this->get("update_targets.$section", []);
            
            // If it's an array of lists (with key-value elements)
            if (is_array($sectionData) && !empty($sectionData)) {
                // Check if it's a direct associative array
                if (!isset($sectionData[0])) {
                    return $sectionData;
                }
                
                // If it's an array of lists, convert to associative array
                $result = [];
                foreach ($sectionData as $key => $value) {
                    if (is_string($key)) {
                        $result[$key] = $value;
                    }
                }
                return $result;
            }
            
            return $sectionData;
        }
        return $this->get('update_targets', []);
    }

    /**
     * Check if a file/folder should be updated
     */
    public function shouldUpdate($section, $item)
    {
        // First try to retrieve directly
        $path = "update_targets.$section.$item";
        if ($this->exists($path)) {
            return $this->get($path);
        }
        
        // If not found, retrieve section and search in parsed data
        $sectionData = $this->getUpdateTargets($section);
        
        if (is_array($sectionData) && array_key_exists($item, $sectionData)) {
            return $sectionData[$item];
        }
        
        // If the specific item doesn't exist, check if the entire section is enabled
        $sectionPath = "update_targets.$section";
        $sectionValue = $this->get($sectionPath);
        
        // If the section is a boolean true, everything is enabled
        return $sectionValue === true;
    }

    /**
     * Display formatted configuration (debug only)
     */
    public function displayConfig()
    {
        echo "=== Epaphrodites Framework Configuration ===\n";
        echo "Version: " . $this->getVersion() . "\n";
        echo "Package: " . $this->getPackage() . "\n\n";

        echo "Update types:\n";
        $updateTypes = $this->get('update.type', []);
        foreach ($updateTypes as $type => $enabled) {
            echo "  - $type: " . ($enabled ? 'Enabled' : 'Disabled') . "\n";
        }

        echo "\nUpdate targets:\n";
        $this->displayArray($this->getUpdateTargets(), 1);
    }

    /**
     * Display array recursively (debug only)
     */
    private function displayArray($array, $level = 0)
    {
        if (!is_array($array)) {
            return;
        }
        
        $indent = str_repeat('  ', $level);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                echo $indent . "$key:\n";
                $this->displayArray($value, $level + 1);
            } else {
                $status = $value === true ? 'Enabled' : 
                         ($value === false ? 'Disabled' : $value);
                echo $indent . "$key: $status\n";
            }
        }
    }

    /**
     * Debug method to display complete configuration structure
     */
    public function debugConfig()
    {
        echo "Complete configuration structure:\n";
        print_r($this->config);
    }
}