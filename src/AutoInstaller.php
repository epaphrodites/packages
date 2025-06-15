<?php
namespace Epaphrodites\Packages;

class AutoInstaller
{
    private const CONFIG_FILES = [
        'epaphrodites-config.yaml',
        'synchrone'
    ];

    public static function init(): void
    {
        if (self::isComposerExecution()) {
            self::runInstallation();
        }
    }

    private static function isComposerExecution(): bool
    {
        return defined('COMPOSER_VENDOR_DIR') || php_sapi_name() === 'cli';
    }

    private static function runInstallation(): void
    {
        $rootDir = getcwd();
        $success = true;

        foreach (self::CONFIG_FILES as $file) {
            $filePath = "$rootDir/$file";
            
            if (!file_exists($filePath)) {
                $success = self::createFile($filePath) && $success;
            } else {
                echo "ℹ️  $file existe déjà\n";
            }
        }

        self::logResult($success);
    }

    private static function createFile(string $path): bool
    {
        $content = match(basename($path)) {
            'epaphrodites-config.yaml' => "# Configuration par défaut\nversion: 1.0",
            'synchrone' => "<?php\n// Script de synchronisation",
            default => ''
        };

        if (file_put_contents($path, $content) !== false) {
            echo "✓ $path créé\n";
            return true;
        }

        echo "❌ Erreur création $path\n";
        return false;
    }

    private static function logResult(bool $success): void
    {
        file_put_contents(
            'epaphrodites_install.log',
            date('[Y-m-d H:i:s]') . ' - ' . ($success ? 'SUCCÈS' : 'ÉCHEC') . "\n",
            FILE_APPEND
        );

        echo $success 
            ? "\n🎉 Configuration terminée!\n"
            : "\n⚠️  Des erreurs sont survenues\n";
    }
}

// Auto-exécution sécurisée
if (php_sapi_name() === 'cli' && !isset($_SERVER['HTTP_HOST'])) {
    AutoInstaller::init();
}