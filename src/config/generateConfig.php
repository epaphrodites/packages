<?php

namespace Epaphrodites\Packages\config;

use Epaphrodites\Packages\config\PackageUpdater;
use Epaphrodites\Packages\config\EpaphroditesConfigReader;

class generateConfig
{

    private function readYamlFile():EpaphroditesConfigReader {

        $rootDir = getcwd();
        $yamlPath = $rootDir . '/synchrone-config.yaml';
        
        if (!file_exists($yamlPath)) {
            throw new \Exception("Ensure synchrone-config.yaml is located at the root of your project.");
        }
        
        $reader = new EpaphroditesConfigReader($yamlPath);

        return $reader;
    }

    public static function lunch(
        string $option
    ){
        
        if($option == 'install'){
            
            $instance = new self();
            return $instance->installComponents();
        }

        if($option == 'update'){

            $instance = new self();
            return $instance->getNewsComponentsFromPackagist();
        }

        return 'Unrecognized command';
    }

    private function getNewsComponentsFromPackagist():array{

        $updater = new PackageUpdater(true);
        $result = $updater->updateEpaphroditesPackage();

        return $result;
    }

    private function installComponents(){
        
        $yamlFileContent = $this->readYamlFile();

        // Évaluation préalable des flags
        $allUpdate = $yamlFileContent->isUpdateTypeEnabled('all');
        $specificUpdate = $yamlFileContent->isUpdateTypeEnabled('specific');
        $newComponentUpdate = $yamlFileContent->isUpdateTypeEnabled('new');

        // Vérification de conflit logique
        if ($allUpdate && $specificUpdate) {
            throw new \LogicException("Conflit dans le fichier YAML : 'all' et 'specific' ne peuvent pas être activés ensemble.");
        }        

        // Traitement des mises à jour générales ou spécifiques
        $generalOrSpecificUpdate = match (true) {
            $allUpdate => $this->generalUpdate($yamlFileContent),
            $specificUpdate => $this->specificUpdate($yamlFileContent),
            default => '⚠️ Aucune mise à jour générale ou spécifique détectée.',
        };

        echo $generalOrSpecificUpdate . PHP_EOL;

        // Traitement des nouvelles composantes
        $newComponentsUpdate = match (true) {
            $newComponentUpdate => $this->newsComponentsUpdate($yamlFileContent),
            default => '⚠️ Aucune mise à jour de nouvelles composantes demandée.',
        };

        echo $newComponentsUpdate . PHP_EOL;
    }

    private function generalUpdate($yamlFileContent){


        $rootPath = getcwd();
        $vendorPackagePath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/init-ressources';

        $backupPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/old-ressources';
        
        $matches = $this->checkDirectoryCorrespondence($rootPath, $vendorPackagePath);
        
        if (!empty($matches)) {
            $this->replaceMatchedFilesFromVendorWithDatedBackup($rootPath, $vendorPackagePath, $matches, $backupPath);
        } else {
            echo "🔍 Aucune correspondance trouvée. Aucun remplacement effectué." . PHP_EOL;
        }
    }

    private function checkDirectoryCorrespondence(string $rootPath, string $targetPath): array
    {
        $directoriesToCheck = ['bin', 'public/layouts'];
        $matches = [];

        foreach ($directoriesToCheck as $dirName) {
            $sourceDir = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dirName;
            $targetDir = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dirName;

            if (!is_dir($sourceDir) || !is_dir($targetDir)) {
                continue;
            }

            $sourceItems = scandir($sourceDir);
            $targetItems = scandir($targetDir);

            $sourceItems = array_diff($sourceItems, ['.', '..']);
            $targetItems = array_diff($targetItems, ['.', '..']);

            foreach ($sourceItems as $item) {
                if (in_array($item, $targetItems)) {
                    $matches[] = [
                        'directory' => $dirName,
                        'item' => $item,
                        'type' => is_dir($sourceDir . '/' . $item) ? 'directory' : 'file'
                    ];
                }
            }
        }

        return $matches;
    }

    private function replaceMatchedFilesFromVendorWithDatedBackup(string $rootPath, string $vendorPath, array $matches, string $backupBasePath): void
    {
        $dateFolder = date('Y-m-d_His');
        $backupPath = rtrim($backupBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dateFolder;
        $logMessages = [];
        $replacedCount = 0;

        foreach ($matches as $match) {
            $dir = $match['directory'];
            $item = $match['item'];

            $source = $vendorPath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $item;
            $destination = $rootPath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $item;
            $backup = $backupPath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $item;

            // Sauvegarde de l'ancien fichier/dossier s'il existe
            if (file_exists($destination)) {
                if (!is_dir(dirname($backup))) {
                    mkdir(dirname($backup), 0777, true);
                }
                rename($destination, $backup);
                $logMessages[] = "🕓 Sauvegarde : $dir/$item → old-ressources/$dateFolder/$dir/$item";
            }

            // Remplacement depuis vendor
            if (is_file($source)) {
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0777, true);
                }
                copy($source, $destination);
            } elseif (is_dir($source)) {
                $this->copyDirectory($source, $destination);
            }

            $logMessages[] = "✅ Remplacé  : $dir/$item";
            $replacedCount++;
        }

        // Affichage console
        echo PHP_EOL . "📦 Actions effectuées :" . PHP_EOL;
        foreach ($logMessages as $msg) {
            echo $msg . PHP_EOL;
        }

        echo PHP_EOL . "✅ Total de fichiers/dossiers remplacés : $replacedCount" . PHP_EOL;

        // Enregistrement du log
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        $logFile = $backupPath . DIRECTORY_SEPARATOR . 'log.txt';
        file_put_contents($logFile, implode(PHP_EOL, $logMessages));
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $src = $source . DIRECTORY_SEPARATOR . $item;
            $dst = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($src)) {
                $this->copyDirectory($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
    }

    private function specificUpdate($yamlFileContent){
        
        // Get section
        $yamlFileContent->getUpdateTargets('config');

        // Get
        $yamlFileContent->shouldUpdate('config', 'Config.ini');

    }

    private function newsComponentsUpdate($yamlFileContent){
        
    }

}