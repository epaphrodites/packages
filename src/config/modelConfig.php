<?php

namespace Epaphrodites\Packages;

class ModelConfig
{
    
    public static function createDefaultUpdateYaml($filePath) {
        $content = <<<YAML
    # Update configuration for the Epaphrodites framework
    # Automatically generated during installation
    version: "v0.01-stable"
    package: "packages/epaphrodites"

    # Available update modes
    # all: Update all files
    # specific: Update only specific files
    # new: Update only new files
    update:
        type:
            all: true
            specific: false
            new: true

    # Specific folders/files to be updated
    update_targets:
        bin:
            config:
                Config.ini: true
                Config.json: true
                email.ini: true
                setDirectory.php: true

            controllers:
                controllerMap:
                    routesConfig.py: true
                controllers:
                    apiControllers.py: true

            database:
                config: true
                gearShift: true
                query: true
                seeders: true

            epaphrodites:
                api: true
                auth: true
                cbuild: true
                chatBot: true
                Console: true
                constant: true
                Contracts: true
                CsrfToken: true
                danho: true
                env: true
                epaphAI: true
                EpaphMozart: true
                ErrorsExceptions: true
                ExcelFiles: true
                Extension: true
                heredia: true
                Kernel: true
                path: true
                python: true
                QRCodes: true
                shares: true
                translate: true
                yedidiah: true

        public:
            layouts:
                display: true
                template: true
                widgets: true

    YAML;
    
        return file_put_contents($filePath, $content) !== false;
    }

public static function createDefaultSynchronePhp($filePath) {
    $content = <<<'PHP'
<?php
# =============== PHP Extension Installer for linux and macos =================
# Supports MacOS, Ubuntu, Debian, and other Linux distributions.
# Author: Y'srael Aimé N'dri
# License: MIT
# ============================================================================= #

/*
    |--------------------------------------------------------------------------
    | Run main directory containt which first Config
    |--------------------------------------------------------------------------
*/   
    require 'bin/config/SetDirectory.php';

/*
    |--------------------------------------------------------------------------
    | Run autoloader of composer
    |--------------------------------------------------------------------------
*/ 
    require _DIR_VENDOR_.'/autoload.php';

    array_shift($argv);

    if (count($argv) > 0) {
        $command = implode(' ', $argv);
        
        $result = match ($command) {
            "-i" => requireComponent('install'),
            "-u" => requireComponent('update'),
            default => "Unrecognized command." . PHP_EOL
        };
        
        echo $result;
    } else {
        echo "No command specified." . PHP_EOL;
    }    

    function requireComponent($option) {
        Epaphrodites\Packages\config\GenerateConfig::lunch($option);
    }

PHP;

    return file_put_contents($filePath, $content) !== false;
}    
}