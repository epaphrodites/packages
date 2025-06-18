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

        $correspondances = $this->checkDirectoryCorrespondence($rootPath, $vendorPackagePath);

        if (!empty($correspondances)) {
            foreach ($correspondances as $match) {
                echo "Correspondance trouvée dans '{$match['directory']}': {$match['item']} ({$match['type']})" . PHP_EOL;
            }
        } else {
            echo "Aucune correspondance trouvée entre les dossiers." . PHP_EOL;
        }

        die;
    }

    private function specificUpdate($yamlFileContent){
        
        // Get section
        $yamlFileContent->getUpdateTargets('config');

        // Get
        $yamlFileContent->shouldUpdate('config', 'Config.ini');

    }

    private function newsComponentsUpdate($yamlFileContent){
        
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

            // Élimine les éléments système . et ..
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

}