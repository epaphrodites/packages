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
            $allUpdate => $this->generalUpdate(),
            $specificUpdate => $this->specificUpdate($yamlFileContent),
            default => '⚠️ Aucune mise à jour générale ou spécifique détectée.',
        };

        echo $generalOrSpecificUpdate . PHP_EOL;

        // Traitement des nouvelles composantes
        $newComponentsUpdate = match (true) {
            $newComponentUpdate => $this->newsComponentsUpdate(),
            default => '⚠️ Aucune mise à jour de nouvelles composantes demandée.',
        };

        echo $newComponentsUpdate . PHP_EOL;
    }

    private function generalUpdate()
    {
        $rootPath = getcwd();
        $vendorPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/init-ressources';
        $backupPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/old-ressources';
        $directoriesToCheck = ['bin', 'public/layouts', 'config'];
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

            // Traitement sélectif : seulement les fichiers présents dans vendor
            $this->processDirectorySelectively($vendorDir, $rootDir, $backupPath, $dirName, $logMessages, $operationCount);
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
        if (!empty($logMessages)) {
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0777, true);
            }
            file_put_contents($backupPath . DIRECTORY_SEPARATOR . 'log.txt', implode(PHP_EOL, $logMessages));
        }
    }

    private function processDirectorySelectively(
        string $vendorDir,
        string $rootDir,
        string $backupPath,
        string $dirName,
        array &$logMessages,
        int &$operationCount
    ): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $vendorItemPath) {
            $relativePath = str_replace($vendorDir . DIRECTORY_SEPARATOR, '', $vendorItemPath);
            $targetPath = $rootDir . DIRECTORY_SEPARATOR . $relativePath;
            $backupTarget = $backupPath . DIRECTORY_SEPARATOR . $dirName . DIRECTORY_SEPARATOR . $relativePath;

            // Si c'est un dossier, on le crée s'il n'existe pas
            if (is_dir($vendorItemPath)) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0777, true);
                    $logMessages[] = "📁 Dossier créé : $dirName/$relativePath";
                    $operationCount++;
                }
                continue;
            }

            // Si c'est un fichier
            if (is_file($vendorItemPath)) {
                // Créer le dossier parent si nécessaire
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0777, true);
                }

                // Si le fichier existe déjà dans le projet
                if (file_exists($targetPath)) {
                    // Sauvegarder avant remplacement
                    if (!is_dir(dirname($backupTarget))) {
                        mkdir(dirname($backupTarget), 0777, true);
                    }
                    
                    // Copier vers backup au lieu de rename pour éviter les conflits
                    copy($targetPath, $backupTarget);
                    $logMessages[] = "🕓 Sauvegarde : $dirName/$relativePath → old-ressources/" . basename($backupPath) . "/$dirName/$relativePath";
                    
                    // Remplacer le fichier
                    copy($vendorItemPath, $targetPath);
                    $logMessages[] = "🔄 Remplacé : $dirName/$relativePath";
                    $operationCount++;
                } else {
                    // Le fichier n'existe pas dans le projet, on l'ajoute
                    copy($vendorItemPath, $targetPath);
                    $logMessages[] = "➕ Ajouté : $dirName/$relativePath";
                    $operationCount++;
                }
            }
        }
    }

    private function newsComponentsUpdate()
    {
        $rootPath = getcwd();
        $newComponentPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/new-ressources';
        $backupPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/old-ressources';
    
        $correspondances = $this->checkDirectoryCorrespondence($rootPath, $newComponentPath);
    
        if (!empty($correspondances)) {
            foreach ($correspondances as $match) {
                echo "Correspondance trouvée dans '{$match['directory']}': {$match['item']} ({$match['type']})" . PHP_EOL;
    
                $this->backupAndReplaceItem(
                    $match['directory'],
                    $match['item'],
                    $newComponentPath,
                    $rootPath,
                    $backupPath
                );
            }
        } else {
            echo "Aucune correspondance trouvée entre les dossiers." . PHP_EOL;
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
    
            // Exclut . et ..
            $sourceItems = array_diff($sourceItems, ['.', '..']);
            $targetItems = array_diff($targetItems, ['.', '..']);
    
            foreach ($sourceItems as $item) {
                if (in_array($item, $targetItems)) {
                    $matches[] = [
                        'directory' => $dirName,
                        'item' => $item,
                        'type' => is_dir($sourceDir . DIRECTORY_SEPARATOR . $item) ? 'directory' : 'file'
                    ];
                }
            }
        }
    
        return $matches;
    }
    
    private function backupAndReplaceItem(string $directory, string $item, string $sourceBasePath, string $destinationBasePath, string $backupBasePath): void
    {
        $source = $sourceBasePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $item;
        $destination = $destinationBasePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $item;
        $backup = $backupBasePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $item;
    
        if (file_exists($destination)) {
            if (is_dir($destination)) {
                $this->recursiveCopy($destination, $backup);
            } else {
                @mkdir(dirname($backup), 0755, true);
                copy($destination, $backup);
            }
            echo "Sauvegardé : $backup" . PHP_EOL;
        }
    
        // Remplacement
        if (is_dir($source)) {
            // Supprimer le dossier destination avant de remplacer
            $this->deleteRecursively($destination);
            $this->recursiveCopy($source, $destination);
            echo "Dossier remplacé : $destination" . PHP_EOL;
        } elseif (is_file($source)) {
            copy($source, $destination);
            echo "Fichier remplacé : $destination" . PHP_EOL;
        }
    }
    
    private function recursiveCopy(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
    
        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
    
            $src = $source . DIRECTORY_SEPARATOR . $item;
            $dst = $destination . DIRECTORY_SEPARATOR . $item;
    
            if (is_dir($src)) {
                $this->recursiveCopy($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
    }
    
    private function deleteRecursively(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
    
        if (is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $this->deleteRecursively($path . DIRECTORY_SEPARATOR . $item);
            }
            rmdir($path);
        }
    }
    
    private function specificUpdate($yamlFileContent)
{
    $rootPath = getcwd();
    $vendorPath = $rootPath . '/vendor/epaphrodites/packages/src/epaphrodites/init-ressources';

    $getSpecific = $yamlFileContent->getUpdateTargets();

    $directoriesToCheck = ['bin', 'public'];

    foreach ($directoriesToCheck as $baseDir) {
        if (!isset($getSpecific[$baseDir])) {
            continue;
        }

        $baseStructure = $getSpecific[$baseDir];

        foreach ($baseStructure as $subDir => $content) {
            $subDirPath = "$vendorPath/$baseDir/$subDir";
            $relativePath = "$baseDir/$subDir";

            // Si l’entrée est un tableau (sous-structure), on explore
            if (is_array($content)) {
                $this->testMatches($content, $subDirPath, $relativePath);
            } elseif ($content === true) {
                // Fichier ou dossier directement sous bin/
                if (file_exists($subDirPath)) {
                    echo "✅ Correspondance trouvée : $relativePath" . PHP_EOL;
                } else {
                    echo "❌ Introuvable : $relativePath" . PHP_EOL;
                }
            }
        }
    }

    echo "🎉 Test de correspondance terminé." . PHP_EOL;
    die;
}


private function testMatches(array $structure, string $targetBase, string $relativeBase): void
{
    foreach ($structure as $name => $value) {
        $targetPath = $targetBase . '/' . $name;
        $relativePath = $relativeBase . '/' . $name;

        if (is_array($value)) {
            $this->testMatches($value, $targetPath, $relativePath);
        } elseif ($value === true) {
            if (file_exists($targetPath)) {
                echo "✅ Correspondance trouvée : $relativePath" . PHP_EOL;
            } else {
                echo "❌ Introuvable : $relativePath" . PHP_EOL;
            }
        }
    }
}



}