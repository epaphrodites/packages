<?php

require "config/modelConfig.php";

use Ndri\config\modelConfig;

$rootDir = getcwd();

$packageDir = __DIR__;

echo "🚀 Installation de packages/epaphrodites...\n";

function createFileIfNotExists($source, $destination, $filename) {
    if (!file_exists($destination)) {
        if (file_exists($source)) {
            if (copy($source, $destination)) {
                echo "✓ Fichier {$filename} créé avec succès\n";
                return true;
            } else {
                echo "❌ Erreur lors de la création de {$filename}\n";
                return false;
            }
        } else {
            echo "⚠️  Fichier source {$filename} introuvable\n";
            return false;
        }
    } else {
        echo "ℹ️  Fichier {$filename} existe déjà, pas de modification\n";
        return true;
    }
}

$success = true;

$updateYamlPath = $rootDir . '/epaphrodites-config.yaml';
if (!file_exists($updateYamlPath)) {
    if (modelConfig::createDefaultUpdateYaml($updateYamlPath)) {
        echo "✓ Fichier update.yaml créé avec succès\n";
    } else {
        echo "❌ Erreur lors de la création de epaphrodites-config.yaml\n";
        $success = false;
    }
} else {
    echo "ℹ️  Fichier update.yaml existe déjà, pas de modification\n";
}

$synchronePhpPath = $rootDir . '/synchrone';
if (!file_exists($synchronePhpPath)) {
    if (modelConfig::createDefaultSynchronePhp($synchronePhpPath)) {
        echo "✓ Fichier synchrone créé avec succès\n";
    } else {
        echo "❌ Erreur lors de la création de synchrone\n";
        $success = false;
    }
} else {
    echo "ℹ️  Fichier synchrone existe déjà, pas de modification\n";
}

if ($success) {
    echo "\n🎉 Installation terminée avec succès !\n";
    echo "📁 Fichiers créés à la racine de votre projet :\n";
    echo "   - update.yaml (configuration de mise à jour)\n";
    echo "   - synchrone (script de synchronisation)\n";
    echo "\n📖 Consultez la documentation sur https://epaphrodite.org\n";
} else {
    echo "\n⚠️  Installation terminée avec des erreurs\n";
    exit(1);
}