<?php
/**
 * Script d'installation automatique pour packages/epaphrodites
 * Crée automatiquement les fichiers de configuration à la racine du projet
 */

// Obtenir le répertoire racine du projet (là où se trouve composer.json)
$rootDir = getcwd();

// Répertoire source de votre librairie
$packageDir = __DIR__;

echo "🚀 Installation de packages/epaphrodites...\n";

/**
 * Fonction pour créer un fichier s'il n'existe pas
 */
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

/**
 * Créer le contenu par défaut pour update.yaml
 */
function createDefaultUpdateYaml($filePath) {
    $content = <<<YAML
# Configuration de mise à jour - packages/epaphrodites
# Généré automatiquement lors de l'installation

version: "1.0.0"
package: "packages/epaphrodites"
auto_update: true
check_interval: 3600
last_check: null

# Paramètres de mise à jour
update:
  enabled: true
  backup_before_update: true
  notification: true
  
# URLs de vérification
urls:
  check: "https://epaphrodite.org/api/check-version"
  download: "https://epaphrodite.org/api/download"

YAML;

    return file_put_contents($filePath, $content) !== false;
}

/**
 * Créer le contenu par défaut pour synchrone.php
 */
function createDefaultSynchronePhp($filePath) {
    $content = <<<'PHP'
<?php
/**
 * Fichier de synchronisation - packages/epaphrodites
 * Généré automatiquement lors de l'installation
 */

// Configuration de base
$synchroneConfig = [
    'enabled' => true,
    'timeout' => 30,
    'retry_attempts' => 3,
    'log_file' => 'synchrone.log',
    'debug_mode' => false
];

/**
 * Fonction principale de synchronisation
 */
function synchronizeData($config = []) {
    global $synchroneConfig;
    
    $config = array_merge($synchroneConfig, $config);
    
    if (!$config['enabled']) {
        return ['status' => 'disabled', 'message' => 'Synchronisation désactivée'];
    }
    
    try {
        // Votre logique de synchronisation ici
        
        if ($config['debug_mode']) {
            echo "🔄 Début de la synchronisation...\n";
        }
        
        // Simulation d'une synchronisation
        sleep(1);
        
        return [
            'status' => 'success',
            'message' => 'Synchronisation réussie',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        $error = [
            'status' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log de l'erreur si configuré
        if (!empty($config['log_file'])) {
            file_put_contents(
                $config['log_file'], 
                json_encode($error) . "\n", 
                FILE_APPEND | LOCK_EX
            );
        }
        
        return $error;
    }
}

// Exemple d'utilisation
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $result = synchronizeData();
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
PHP;

    return file_put_contents($filePath, $content) !== false;
}

// Créer les fichiers
$success = true;

// 1. Créer update.yaml
$updateYamlPath = $rootDir . '/update.yaml';
if (!file_exists($updateYamlPath)) {
    if (createDefaultUpdateYaml($updateYamlPath)) {
        echo "✓ Fichier update.yaml créé avec succès\n";
    } else {
        echo "❌ Erreur lors de la création de update.yaml\n";
        $success = false;
    }
} else {
    echo "ℹ️  Fichier update.yaml existe déjà, pas de modification\n";
}

// 2. Créer synchrone.php
$synchronePhpPath = $rootDir . '/synchrone.php';
if (!file_exists($synchronePhpPath)) {
    if (createDefaultSynchronePhp($synchronePhpPath)) {
        echo "✓ Fichier synchrone.php créé avec succès\n";
    } else {
        echo "❌ Erreur lors de la création de synchrone.php\n";
        $success = false;
    }
} else {
    echo "ℹ️  Fichier synchrone.php existe déjà, pas de modification\n";
}

// 3. Créer un fichier .gitignore pour exclure les logs (optionnel)
$gitignorePath = $rootDir . '/.gitignore';
$gitignoreContent = "# Logs de synchronisation\nsynchrone.log\n";

if (file_exists($gitignorePath)) {
    $existingContent = file_get_contents($gitignorePath);
    if (strpos($existingContent, 'synchrone.log') === false) {
        file_put_contents($gitignorePath, "\n" . $gitignoreContent, FILE_APPEND | LOCK_EX);
        echo "✓ Ajout de synchrone.log dans .gitignore\n";
    }
} else {
    file_put_contents($gitignorePath, $gitignoreContent);
    echo "✓ Fichier .gitignore créé avec synchrone.log\n";
}

if ($success) {
    echo "\n🎉 Installation terminée avec succès !\n";
    echo "📁 Fichiers créés à la racine de votre projet :\n";
    echo "   - update.yaml (configuration de mise à jour)\n";
    echo "   - synchrone.php (script de synchronisation)\n";
    echo "\n📖 Consultez la documentation sur https://epaphrodite.org\n";
} else {
    echo "\n⚠️  Installation terminée avec des erreurs\n";
    exit(1);
}