<?php

namespace Ndri\Installer;

use Ndri\config\modelConfig;

class postInstall
{
    public static function install()
    {
        $rootDir = getcwd();
        echo "🚀 Début de l'installation de epaphrodites/packages...\n";

        $success = true;
        
        // Configuration YAML
        $yamlPath = $rootDir . '/epaphrodites-config.yaml';
        if (!file_exists($yamlPath)) {
            if (modelConfig::createDefaultUpdateYaml($yamlPath)) {
                echo "✓ Fichier epaphrodites-config.yaml créé\n";
            } else {
                echo "❌ Erreur création epaphrodites-config.yaml\n";
                $success = false;
            }
        }

        // Fichier synchrone
        $synchronePath = $rootDir . '/synchrone';
        if (!file_exists($synchronePath)) {
            if (modelConfig::createDefaultSynchronePhp($synchronePath)) {
                echo "✓ Fichier synchrone créé\n";
            } else {
                echo "❌ Erreur création synchrone\n";
                $success = false;
            }
        }

        if ($success) {
            echo "\n✅ Installation réussie!\n";
            return 0;
        } else {
            echo "\n⚠️ Des erreurs sont survenues\n";
            return 1;
        }
    }
}