<?php

namespace Epaphrodites\Packages;

use Epaphrodites\Packages\PackageUpdater;
use Epaphrodites\Packages\EpaphroditesConfigReader;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;
use LogicException;

/**
 * Configuration Generator for Epaphrodites Package Management
 * 
 * Handles installation and updates of package components with backup functionality
 */
class GenerateConfig
{

    use Constant;

    /**
     * Read YAML configuration file
     * 
     * @return EpaphroditesConfigReader
     * @throws Exception If config file not found
     */
    private function readYamlConfig()
    {
        $rootDir = getcwd();
        $yamlPath = $rootDir . DIRECTORY_SEPARATOR . self::YAML_CONFIG_FILE;
        
        if (!file_exists($yamlPath)) {
            throw new Exception("Please ensure " . self::YAML_CONFIG_FILE . " is located at the root of your project.");
        }
        
        return new EpaphroditesConfigReader($yamlPath);
    }

    /**
     * Main entry point for package operations
     * 
     * @param string $option Operation type: 'install' or 'update'
     * @return string|array Result of the operation
     */
    public static function lunch(string $option)
    {
        $instance = new self();
        
        return match ($option) {
            'install' => $instance->installComponents(),
            'update' => $instance->getLatestComponentsFromPackagist(),
            default => 'Error: Unrecognized command. Use "install" or "update".',
        };
    }

    /**
     * Get latest components from Packagist
     * 
     * @return array Update results
     */
    private function getLatestComponentsFromPackagist(): array
    {
        $rootDir = getcwd();
        $composerFile = $rootDir . '/composer.json';
        $updater = new PackageUpdater(true, $composerFile); 
        return $updater->updateEpaphroditesPackage();
    }

    /**
     * Install components based on YAML configuration
     * 
     * @return void
     */
    private function installComponents(): void
    {
        $yamlConfig = $this->readYamlConfig();

        // Evaluate configuration flags
        $allUpdate = $yamlConfig->isUpdateTypeEnabled('all');
        $specificUpdate = $yamlConfig->isUpdateTypeEnabled('specific');
        $newComponentUpdate = $yamlConfig->isUpdateTypeEnabled('new');

        // Check for logical conflicts
        if ($allUpdate && $specificUpdate) {
            throw new LogicException("Configuration conflict: 'all' and 'specific' cannot be enabled simultaneously.");
        }

        // Process general or specific updates
        $generalOrSpecificResult = match (true) {
            $allUpdate => $this->performGeneralUpdate(),
            $specificUpdate => $this->performSpecificUpdate($yamlConfig),
            default => '⚠️  No general or specific updates detected.',
        };

        echo $generalOrSpecificResult . PHP_EOL;

        // Process new component updates
        $newComponentResult = match (true) {
            $newComponentUpdate => $this->performNewComponentsUpdate(),
            default => '⚠️  No new component updates requested.',
        };

        echo $newComponentResult . PHP_EOL;
    }

    /**
     * Perform general update of all components
     * 
     * @return string Update result message
     */
    private function performGeneralUpdate(): string
    {
        $rootPath = getcwd();
        $vendorPath = $rootPath . self::VENDOR_INIT_PATH;
        $backupPath = $rootPath . self::VENDOR_BACKUP_PATH;
        
        $this->mergeDirectoriesFromVendor($vendorPath, $rootPath, $backupPath, self::STANDARD_DIRECTORIES);
        
        return "\033[32m✅ General update done.\033[0m";
    }

    /**
     * Merge directories from vendor to project root with backup
     * 
     * @param string $vendorPath Source vendor path
     * @param string $rootPath Project root path
     * @param string $backupBasePath Backup base path
     * @param array $directoriesToCheck Directories to process
     * @return void
     */
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
            
            if (!is_dir($vendorDir)) {
                continue;
            }

            $this->processDirectorySelectively($vendorDir, $rootDir, $backupPath, $dirName, $logMessages, $operationCount);
        }

        $this->displayOperationResults($logMessages, $operationCount, $backupPath);
    }

    /**
     * Process directory selectively (only files present in vendor)
     * 
     * @param string $vendorDir Source vendor directory
     * @param string $rootDir Target root directory
     * @param string $backupPath Backup path
     * @param string $dirName Directory name for logging
     * @param array $logMessages Log messages array (by reference)
     * @param int $operationCount Operation counter (by reference)
     * @return void
     */
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

            if (is_dir($vendorItemPath)) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, self::PERMISSIONS, true);
                    $logMessages[] = "📁 Directory created: $dirName/$relativePath";
                    $operationCount++;
                }
                continue;
            }

            if (is_file($vendorItemPath)) {
                $this->ensureDirectoryExists(dirname($targetPath));

                if (file_exists($targetPath)) {
                    // Backup existing file before replacement
                    $this->ensureDirectoryExists(dirname($backupTarget));
                    copy($targetPath, $backupTarget);
                    $logMessages[] = "🕓 Backed up: $dirName/$relativePath → old-ressources/" . basename($backupPath) . "/$dirName/$relativePath";
                    
                    copy($vendorItemPath, $targetPath);
                    $logMessages[] = "🔄 Replaced: $dirName/$relativePath";
                    $operationCount++;
                } else {
                    // Add new file
                    copy($vendorItemPath, $targetPath);
                    $logMessages[] = "➕ Added: $dirName/$relativePath";
                    $operationCount++;
                }
            }
        }
    }

    /**
     * Display operation results and save log
     * 
     * @param array $logMessages Log messages
     * @param int $operationCount Number of operations performed
     * @param string $backupPath Backup path for log file
     * @return void
     */
    private function displayOperationResults(array $logMessages, int $operationCount, string $backupPath): void
    {
        if ($operationCount > 0) {
            echo PHP_EOL . "📦 Actions performed:" . PHP_EOL;
            foreach ($logMessages as $message) {
                echo $message . PHP_EOL;
            }
            echo PHP_EOL . "✅ Total: $operationCount files/directories added or replaced" . PHP_EOL;
        } else {
            echo "🔍 No changes made: no files found in vendor directory." . PHP_EOL;
        }

        // Save log file
        if (!empty($logMessages)) {
            $this->ensureDirectoryExists($backupPath);
            file_put_contents($backupPath . DIRECTORY_SEPARATOR . 'operation.log', implode(PHP_EOL, $logMessages));
        }
    }

    /**
     * Perform new components update
     * 
     * @return string Update result message
     */
    private function performNewComponentsUpdate(): string
    {
        $rootPath = getcwd();
        $newComponentPath = $rootPath . self::VENDOR_NEW_PATH;
        $backupPath = $rootPath . self::VENDOR_BACKUP_PATH;

        $correspondences = $this->findDirectoryCorrespondences($rootPath, $newComponentPath);

        if (!empty($correspondences)) {
            foreach ($correspondences as $match) {
                echo "\033[36mMatch found in '{$match['directory']}': {$match['item']} ({$match['type']})\033[0m" . PHP_EOL;

                $source = $newComponentPath . DIRECTORY_SEPARATOR . $match['directory'] . DIRECTORY_SEPARATOR . $match['item'];
                $destination = $rootPath . DIRECTORY_SEPARATOR . $match['directory'] . DIRECTORY_SEPARATOR . $match['item'];
                $backup = $backupPath . DIRECTORY_SEPARATOR . date('Y-m-d_His') . DIRECTORY_SEPARATOR . $match['directory'] . DIRECTORY_SEPARATOR . $match['item'];

                // Backup existing item if it exists
                if (file_exists($destination)) {
                    $this->ensureDirectoryExists(dirname($backup));
                    if (is_dir($destination)) {
                        $this->copyRecursively($destination, $backup);
                        echo "\033[33mBacked up directory: $backup\033[0m" . PHP_EOL;
                    } else {
                        copy($destination, $backup);
                        echo "\033[33mBacked up file: $backup\033[0m" . PHP_EOL;
                    }
                }

                // Copy new item (file or directory)
                if (is_dir($source)) {
                    $this->copyRecursively($source, $destination);
                    echo "\033[32mDirectory copied: $destination\033[0m" . PHP_EOL;
                } elseif (is_file($source)) {
                    $this->ensureDirectoryExists(dirname($destination));
                    copy($source, $destination);
                    echo "\033[32mFile copied: $destination\033[0m" . PHP_EOL;
                }
            }

            // Copy new files that don't exist in destination
            $newIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($newComponentPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($newIterator as $newItem) {
                $relativePath = substr($newItem->getPathname(), strlen($newComponentPath . DIRECTORY_SEPARATOR));
                $destPath = $rootPath . DIRECTORY_SEPARATOR . $relativePath;
                $backupPath = $backupPath . DIRECTORY_SEPARATOR . date('Y-m-d_His') . DIRECTORY_SEPARATOR . $relativePath;

                if (!file_exists($destPath)) {
                    if ($newItem->isDir()) {
                        $this->ensureDirectoryExists($destPath);
                        echo "\033[32mNew directory created: $destPath\033[0m" . PHP_EOL;
                    } else {
                        $this->ensureDirectoryExists(dirname($destPath));
                        copy($newItem->getPathname(), $destPath);
                        echo "\033[32mNew file added: $destPath\033[0m" . PHP_EOL;
                    }
                }
            }

            return "\033[32m✅ New components updated successfully.\033[0m";
        } else {
            return "\033[33m⚠️ No matching directories found.\033[0m";
        }
    }

    /**
     * Find correspondences between directories
     * 
     * @param string $rootPath Project root path
     * @param string $targetPath Target path to compare
     * @return array Array of matching items
     */
    private function findDirectoryCorrespondences(string $rootPath, string $targetPath): array
    {
        $matches = [];

        foreach (self::NEW_COMPONENT_DIRECTORIES as $dirName) {
            $sourceDir = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dirName;
            $targetDir = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $dirName;

            if (!is_dir($sourceDir) || !is_dir($targetDir)) {
                continue;
            }

            $sourceItems = array_diff(scandir($sourceDir), ['.', '..']);
            $targetItems = array_diff(scandir($targetDir), ['.', '..']);

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

    /**
     * Backup and replace an item (file or directory)
     * 
     * @param string $directory Directory name
     * @param string $item Item name
     * @param string $sourceBasePath Source base path
     * @param string $destinationBasePath Destination base path
     * @param string $backupBasePath Backup base path
     * @return void
     */
    private function backupAndReplaceItem(
        string $directory,
        string $item,
        string $sourceBasePath,
        string $destinationBasePath,
        string $backupBasePath
    ): void {
        $source = $sourceBasePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $item;
        $destination = $destinationBasePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $item;
        $backup = $backupBasePath . DIRECTORY_SEPARATOR . date('Y-m-d_His') . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $item;

        // Backup existing item
        if (file_exists($destination)) {
            $this->ensureDirectoryExists(dirname($backup));
            
            if (is_dir($destination)) {
                $this->copyRecursively($destination, $backup);
            } else {
                copy($destination, $backup);
            }
            echo "Backed up: $backup" . PHP_EOL;
        }

        // Replace with new item
        if (is_dir($source)) {
            $this->deleteRecursively($destination);
            $this->copyRecursively($source, $destination);
            echo "Directory replaced: $destination" . PHP_EOL;
        } elseif (is_file($source)) {
            copy($source, $destination);
            echo "File replaced: $destination" . PHP_EOL;
        }
    }

    /**
     * Perform specific update based on YAML configuration
     * 
     * @param EpaphroditesConfigReader $yamlConfig YAML configuration reader
     * @return string Update result message
     */
    private function performSpecificUpdate(EpaphroditesConfigReader $yamlConfig): string
    {
        $rootPath = getcwd();
        $vendorPath = $rootPath . self::VENDOR_INIT_PATH;
        
        $dateSuffix = date('Y-m-d_H-i');
        $backupPath = $rootPath . self::VENDOR_BACKUP_PATH . DIRECTORY_SEPARATOR . $dateSuffix;
        
        $specificTargets = $yamlConfig->getUpdateTargets();
        $directoriesToCheck = ['bin', 'public'];
        
        $statistics = [
            'added' => 0,
            'replaced' => 0,
            'backed_up' => 0,
            'failed' => 0,
            'not_found' => 0
        ];
        
        foreach ($directoriesToCheck as $baseDir) {
            if (!isset($specificTargets[$baseDir])) {
                continue;
            }
            
            $baseStructure = $specificTargets[$baseDir];
            foreach ($baseStructure as $subDir => $content) {
                $subDirPath = $vendorPath . DIRECTORY_SEPARATOR . $baseDir . DIRECTORY_SEPARATOR . $subDir;
                $relativePath = $baseDir . DIRECTORY_SEPARATOR . $subDir;
                $targetPath = $rootPath . DIRECTORY_SEPARATOR . $relativePath;
                
                if (is_array($content)) {
                    $this->processNestedStructure($content, $subDirPath, $relativePath, $vendorPath, $rootPath, $backupPath, $statistics);
                } elseif ($content === true) {
                    if (is_dir($subDirPath)) {
                        $this->copyDirectoryWithBackup($subDirPath, $targetPath, "$backupPath/$relativePath", $statistics);
                    } elseif (file_exists($subDirPath)) {
                        $this->replaceFileWithBackup($subDirPath, $targetPath, "$backupPath/$relativePath", $statistics);
                    } else {
                        // Add missing files from vendor
                        $vendorIterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($vendorPath . DIRECTORY_SEPARATOR . $baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::SELF_FIRST
                        );
                        foreach ($vendorIterator as $vendorItem) {
                            if ($vendorItem->isFile()) {
                                $relativeVendorPath = substr($vendorItem->getPathname(), strlen($vendorPath . DIRECTORY_SEPARATOR));
                                $targetFilePath = $rootPath . DIRECTORY_SEPARATOR . $relativeVendorPath;
                                $backupFilePath = $backupPath . DIRECTORY_SEPARATOR . $relativeVendorPath;
                                if (!file_exists($targetFilePath)) {
                                    $this->replaceFileWithBackup($vendorItem->getPathname(), $targetFilePath, $backupFilePath, $statistics);
                                }
                            }
                        }
                        $statistics['not_found']++;
                    }
                }
            }
        }
        
        $this->displayUpdateSummary($statistics);
        return "\033[32m✅ Specific update completed.\033[0m";
    }

    /**
     * Process nested directory structure
     * 
     * @param array $structure Nested structure array
     * @param string $targetBase Target base path
     * @param string $relativeBase Relative base path
     * @param string $vendorPath Vendor path
     * @param string $rootPath Root path
     * @param string $backupPath Backup path
     * @param array $statistics Statistics array (by reference)
     * @return void
     */
    private function processNestedStructure(
        array $structure,
        string $targetBase,
        string $relativeBase,
        string $vendorPath,
        string $rootPath,
        string $backupPath,
        array &$statistics
    ): void {
        foreach ($structure as $name => $value) {
            $targetPath = $targetBase . DIRECTORY_SEPARATOR . $name;
            $relativePath = $relativeBase . DIRECTORY_SEPARATOR . $name;
            $destinationPath = $rootPath . DIRECTORY_SEPARATOR . $relativePath;
            $backupFilePath = $backupPath . DIRECTORY_SEPARATOR . $relativePath;
            
            if (is_array($value)) {
                $this->processNestedStructure($value, $targetPath, $relativePath, $vendorPath, $rootPath, $backupPath, $statistics);
            } elseif ($value === true) {
                if (is_dir($targetPath)) {
                    $this->copyDirectoryWithBackup($targetPath, $destinationPath, $backupFilePath, $statistics);
                } elseif (file_exists($targetPath)) {
                    $this->replaceFileWithBackup($targetPath, $destinationPath, $backupFilePath, $statistics);
                } else {
                    $statistics['not_found']++;
                }
            }
        }
    }

    /**
     * Copy directory with backup functionality
     * 
     * @param string $sourceDir Source directory
     * @param string $destinationDir Destination directory
     * @param string $backupDir Backup directory
     * @param array $statistics Statistics array (by reference)
     * @return void
     */
    private function copyDirectoryWithBackup(string $sourceDir, string $destinationDir, string $backupDir, array &$statistics): void
    {
        if (!is_dir($sourceDir)) {
            $statistics['failed']++;
            return;
        }
        
        $this->ensureDirectoryExists(dirname($destinationDir));
        $this->ensureDirectoryExists(dirname($backupDir));
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = substr($sourcePath, strlen($sourceDir) + 1);
            $destPath = $destinationDir . DIRECTORY_SEPARATOR . $relativePath;
            $backupPath = $backupDir . DIRECTORY_SEPARATOR . $relativePath;
            
            if ($item->isDir()) {
                $this->ensureDirectoryExists($destPath);
            } else {
                $this->replaceFileWithBackup($sourcePath, $destPath, $backupPath, $statistics);
            }
        }
    }

    /**
     * Replace file with backup functionality
     * 
     * @param string $sourcePath Source file path
     * @param string $destinationPath Destination file path
     * @param string $backupPath Backup file path
     * @param array $statistics Statistics array (by reference)
     * @return void
     */
    private function replaceFileWithBackup(string $sourcePath, string $destinationPath, string $backupPath, array &$statistics): void
    {
        $this->ensureDirectoryExists(dirname($destinationPath));
        
        if (!file_exists($destinationPath)) {
            // New file
            if (copy($sourcePath, $destinationPath)) {
                $statistics['added']++;
            } else {
                $statistics['failed']++;
            }
            return;
        }
        
        // Backup before replacement
        $this->ensureDirectoryExists(dirname($backupPath));
        
        if (copy($destinationPath, $backupPath)) {
            $statistics['backed_up']++;
            
            if (copy($sourcePath, $destinationPath)) {
                $statistics['replaced']++;
            } else {
                $statistics['failed']++;
            }
        } else {
            $statistics['failed']++;
        }
    }

    /**
     * Display update summary
     * 
     * @param array $statistics Operation statistics
     * @return void
     */
    private function displayUpdateSummary(array $statistics): void
    {
        echo "\033[32m🎉 Update completed:\033[0m" . PHP_EOL;
        
        if ($statistics['added'] > 0) {
            echo "\033[32m🆕 {$statistics['added']} file(s) added\033[0m" . PHP_EOL;
        }
        
        if ($statistics['replaced'] > 0) {
            echo "\033[34m📝 {$statistics['replaced']} file(s) replaced\033[0m" . PHP_EOL;
        }
        
        if ($statistics['backed_up'] > 0) {
            echo "\033[33m🗂 {$statistics['backed_up']} file(s) backed up\033[0m" . PHP_EOL;
        }
        
        if ($statistics['not_found'] > 0) {
            echo "\033[31m❌ {$statistics['not_found']} file(s) not found\033[0m" . PHP_EOL;
        }
        
        if ($statistics['failed'] > 0) {
            echo "\033[31m⚠️ {$statistics['failed']} operation(s) failed\033[0m" . PHP_EOL;
        }
        
        if (array_sum($statistics) === 0) {
            echo "\033[33m⚠️ No updates performed.\033[0m" . PHP_EOL;
        }
    }

    /**
     * Copy directory recursively
     * 
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return void
     */
    private function copyRecursively(string $source, string $destination): void
    {
        $this->ensureDirectoryExists($destination);

        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $src = $source . DIRECTORY_SEPARATOR . $item;
            $dst = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($src)) {
                $this->copyRecursively($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
    }

    /**
     * Delete directory or file recursively
     * 
     * @param string $path Path to delete
     * @return void
     */
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

    /**
     * Ensure directory exists, create if not
     * 
     * @param string $directory Directory path
     * @return void
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, self::PERMISSIONS, true);
        }
    }
}