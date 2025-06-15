<?php

/**
 * EpaphroditesConfigReader - Lecteur de configuration YAML
 * 
 * Classe dédiée uniquement à la lecture du fichier de configuration YAML
 * du framework Epaphrodites. Aucune fonctionnalité de sauvegarde ou modification.
 */

namespace Ndri\config;
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
     * Charge et parse le fichier YAML
     */
    private function loadConfig()
    {
        if (!file_exists($this->yamlFile)) {
            throw new Exception("Le fichier YAML n'existe pas : " . $this->yamlFile);
        }

        $content = file_get_contents($this->yamlFile);
        if ($content === false) {
            throw new Exception("Impossible de lire le fichier : " . $this->yamlFile);
        }

        $this->config = $this->parseYaml($content);
    }

    /**
     * Parse le contenu YAML en PHP pur
     */
    private function parseYaml($content)
    {
        $lines = explode("\n", $content);
        $result = [];
        $stack = [&$result];
        $levels = [0];

        foreach ($lines as $line) {
            $line = rtrim($line);

            // Ignorer les lignes vides et commentaires
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            // Calculer le niveau d'indentation
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

            // Ajuster la pile selon le niveau
            while (count($levels) > $level + 1) {
                array_pop($stack);
                array_pop($levels);
            }

            // Traitement des éléments de liste
            if (strpos($line, '- ') === 0) {
                $item = trim(substr($line, 2));
                
                if (strpos($item, ':') !== false) {
                    list($key, $value) = $this->splitKeyValue($item);
                    $value = $this->convertValue($value);
                    
                    $current = &$stack[count($stack) - 1];
                    if (!is_array($current)) {
                        $current = [];
                    }
                    $current[$key] = $value;
                } else {
                    $current = &$stack[count($stack) - 1];
                    if (!is_array($current)) {
                        $current = [];
                    }
                    $current[] = $this->convertValue($item);
                }
                continue;
            }

            // Traitement des paires clé:valeur
            if (strpos($line, ':') !== false) {
                list($key, $value) = $this->splitKeyValue($line);
                $value = trim($value);

                if (empty($value)) {
                    $current = &$stack[count($stack) - 1];
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $levels[] = $level + 1;
                } else {
                    $current = &$stack[count($stack) - 1];
                    $current[$key] = $this->convertValue($value);
                }
            }
        }

        return $result;
    }

    /**
     * Sépare une ligne clé:valeur
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
     * Convertit une valeur string en type approprié
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
     * Retourne la configuration complète
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Retourne une valeur par chemin (ex: "update.type.all")
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
     * Vérifie si un chemin existe
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
     * Retourne la version du framework
     */
    public function getVersion()
    {
        return $this->get('version');
    }

    /**
     * Retourne le nom du package
     */
    public function getPackage()
    {
        return $this->get('package');
    }

    /**
     * Vérifie si un type de mise à jour est activé
     */
    public function isUpdateTypeEnabled($type)
    {
        return $this->get("update.type.$type", false);
    }

    /**
     * Retourne les cibles de mise à jour pour une section
     */
    public function getUpdateTargets($section = null)
    {
        if ($section) {
            return $this->get("update_targets.$section", []);
        }
        return $this->get('update_targets', []);
    }

    /**
     * Vérifie si un fichier/dossier doit être mis à jour
     */
    public function shouldUpdate($section, $item)
    {
        $path = "update_targets.$section.$item";
        if ($this->exists($path)) {
            return $this->get($path);
        }
        
        $sectionPath = "update_targets.$section";
        $sectionValue = $this->get($sectionPath);
        
        return $sectionValue === true;
    }

    /**
     * Affiche la configuration formatée (pour debug uniquement)
     */
    public function displayConfig()
    {
        echo "=== Configuration Epaphrodites Framework ===\n";
        echo "Version: " . $this->getVersion() . "\n";
        echo "Package: " . $this->getPackage() . "\n\n";

        echo "Types de mise à jour:\n";
        $updateTypes = $this->get('update.type', []);
        foreach ($updateTypes as $type => $enabled) {
            echo "  - $type: " . ($enabled ? 'Activé' : 'Désactivé') . "\n";
        }

        echo "\nCibles de mise à jour:\n";
        $this->displayArray($this->getUpdateTargets(), 1);
    }

    /**
     * Affiche un tableau récursivement (pour debug uniquement)
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
                $status = $value === true ? 'Activé' : 
                         ($value === false ? 'Désactivé' : $value);
                echo $indent . "$key: $status\n";
            }
        }
    }
}
