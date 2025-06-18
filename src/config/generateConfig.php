<?php

namespace Epaphrodites\Packages\config;

use Epaphrodites\Packages\config\PackageUpdater;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
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

    private function generalUpdate($yamlFileContent)
    {
        $rootPath = getcwd();
        $vendorPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/init-ressources';
        $backupPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/old-ressources';
    
        $directoriesToCheck = ['bin', 'public/layouts', 'config']; // Ajoute tous les dossiers à surveiller ici
        $this->mergeDirectoriesFromVendor($vendorPath, $rootPath, $backupPath, $directoriesToCheck);
    }
    
    private function mergeDirectoriesFromVendor(
        string $vendorPath,
        string $rootPath,
        string $backupBasePath,
        array $directoriesToCheck
    ): void {
        $dateFolder = date('Y-m-d_His');
        $backupPath = rtrim($backupBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dateFolder;
        $logMessages = [];
        $operationCount = 0;
    
        foreach ($directoriesToCheck as $dirName) {
            $vendorDir = $vendorPath . DIRECTORY_SEPARATOR . $dirName;
            $rootDir = $rootPath . DIRECTORY_SEPARATOR . $dirName;
    
            if (!is_dir($vendorDir)) continue;
    
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
    
            foreach ($iterator as $vendorItemPath) {
                $relativePath = str_replace($vendorDir . DIRECTORY_SEPARATOR, '', $vendorItemPath);
                $targetPath = $rootDir . DIRECTORY_SEPARATOR . $relativePath;
                $backupTarget = $backupPath . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $relativePath;
    
                // Si le fichier existe déjà dans le projet, on le sauvegarde avant remplacement
                if (file_exists($targetPath)) {
                    if (!is_dir(dirname($backupTarget))) {
                        mkdir(dirname($backupTarget), 0777, true);
                    }
                    rename($targetPath, $backupTarget);
                    $logMessages[] = "🕓 Sauvegarde : $dirName/$relativePath → old-ressources/$dateFolder/$dirName/$relativePath";
                }
    
                // Création du dossier parent si besoin
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0777, true);
                }
    
                // Copie fichier ou dossier
                if (is_file($vendorItemPath)) {
                    copy($vendorItemPath, $targetPath);
                } elseif (is_dir($vendorItemPath) && !file_exists($targetPath)) {
                    mkdir($targetPath, 0777, true);
                }
    
                $logMessages[] = "✅ Copié ou remplacé : $dirName/$relativePath";
                $operationCount++;
            }
        }
    
        // Affichage console
        if ($operationCount > 0) {
            echo PHP_EOL . "📦 Actions effectuées :" . PHP_EOL;
            foreach ($logMessages as $msg) {
                echo $msg . PHP_EOL;
            }
            echo PHP_EOL . "✅ Total : $operationCount fichiers/dossiers ajoutés ou remplacés" . PHP_EOL;
        } else {
            echo "🔍 Aucun changement effectué : aucun fichier trouvé dans vendor." . PHP_EOL;
        }
    
        // Enregistrement du log
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0777, true);
        }
        file_put_contents($backupPath . DIRECTORY_SEPARATOR . 'log.txt', implode(PHP_EOL, $logMessages));
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