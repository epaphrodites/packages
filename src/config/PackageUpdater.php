<?php

namespace Epaphrodites\Packages;

use Exception;

/**
 * Class for managing epaphrodites/packages package updates
 */
class PackageUpdater
{
    private $verbose;
    private $composerFile;

    /**
     * Constructor
     * 
     * @param bool $verbose Show detailed output (default false)
     * @param string $composerFile Path to composer.json (default ./composer.json)
     */
    public function __construct(
        bool $verbose = false, 
        string $composerFile = './composer.json')
    {
        $this->verbose = $verbose;
        $this->composerFile = $composerFile;
    }

    /**
     * Display colored message
     * 
     * @param string $message Message to display
     * @param string $color Color code (green, yellow, red, blue, cyan, magenta)
     * @param bool $newLine Add new line at the end
     */
    private function displayMessage(
        string $message, 
        string $color = 'white', 
        bool $newLine = true
    ): void{
        if (!$this->verbose) {
            return;
        }

        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'magenta' => "\033[35m",
            'cyan' => "\033[36m",
            'white' => "\033[37m",
            'reset' => "\033[0m"
        ];

        $colorCode = $colors[$color] ?? $colors['white'];
        $resetCode = $colors['reset'];
        
        echo $colorCode . $message . $resetCode . ($newLine ? "\n" : "");
    }

    /**
     * Display waiting animation
     * 
     * @param string $message Base message
     * @param int $duration Duration in seconds
     */
    private function displayWaitingAnimation(
        string $message, 
        int $duration = 3
    ): void{
        if (!$this->verbose) {
            return;
        }

        $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $frameCount = count($frames);
        $iterations = $duration * 10;

        for ($i = 0; $i < $iterations; $i++) {
            $frame = $frames[$i % $frameCount];
            echo "\r\033[36m" . $frame . " " . $message . "\033[0m";
            usleep(100000);
        }
        echo "\r" . str_repeat(' ', strlen($message) + 5) . "\r";
    }

    /**
     * Removes epaphrodites/packages from composer.json
     * 
     * @return bool Success status
     */
    public function removePackageFromComposer(): bool
    {
        try {
            $this->displayMessage("🔍 Checking composer.json file...", 'cyan');
            
            if (!file_exists($this->composerFile)) {
                throw new Exception("composer.json not found at: {$this->composerFile}");
            }

            $this->displayMessage("📖 Reading composer.json file...", 'yellow');
            $composerData = json_decode(file_get_contents($this->composerFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid composer.json format");
            }

            $packageRemoved = false;

            if (isset($composerData['require']['epaphrodites/packages'])) {
                unset($composerData['require']['epaphrodites/packages']);
                $packageRemoved = true;
            }

            if (isset($composerData['require-dev']['epaphrodites/packages'])) {
                unset($composerData['require-dev']['epaphrodites/packages']);
                $packageRemoved = true;
            }

            if ($packageRemoved) {
                $this->displayMessage("🗑️  Removing epaphrodites/packages package...", 'yellow');
                $this->displayWaitingAnimation("Removal in progress", 2);
            }

            $result = file_put_contents(
                $this->composerFile,
                json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            if ($result !== false) {
                $this->displayMessage("✅ Package epaphrodites/packages successfully removed from {$this->composerFile}", 'green');
            } else {
                $this->displayMessage("❌ Failed to remove epaphrodites/packages package", 'red');
            }

            return $result !== false;
        } catch (Exception $e) {
            $this->displayMessage("❌ Error removing package: {$e->getMessage()}", 'red');
            return false;
        }
    }

    /**
     * Updates the epaphrodites/packages package via Composer
     * 
     * @return array Execution result
     */
    public function updateEpaphroditesPackage(): array
    {
        $this->displayMessage("🚀 Preparing epaphrodites/packages download...", 'cyan');
        $this->displayWaitingAnimation("Initialization", 2);

        $this->displayMessage("📦 Starting download via Composer...", 'yellow');
        
        $command = 'composer require epaphrodites/packages';
        $output = [];
        $returnCode = 0;

        $this->displayWaitingAnimation("Download in progress", 3);

        exec($command . ' 2>&1', $output, $returnCode);

        if ($this->verbose) {
            $this->displayMessage("📋 Installation result:", 'magenta');
            foreach ($output as $line) {
                if (strpos($line, 'Installing') !== false || strpos($line, 'Downloading') !== false) {
                    $this->displayMessage("  " . $line, 'cyan');
                } elseif (strpos($line, 'Writing') !== false || strpos($line, 'Generating') !== false) {
                    $this->displayMessage("  " . $line, 'yellow');
                } elseif (strpos($line, 'Package') !== false && strpos($line, 'is up to date') !== false) {
                    $this->displayMessage("  " . $line, 'green');
                } elseif (strpos($line, 'Nothing to') !== false) {
                    $this->displayMessage("  " . $line, 'green');
                } else {
                    $this->displayMessage("  " . $line, 'white');
                }
            }
        }

        if ($returnCode === 0) {
            $this->displayMessage("🎉 Package epaphrodites/packages successfully installed!", 'green');
        } else {
            $this->displayMessage("❌ Failed to install epaphrodites/packages package", 'red');
        }

        return [
            'success' => ($returnCode === 0),
            'output' => $output,
            'return_code' => $returnCode
        ];
    }

    /**
     * Forces update of epaphrodites/packages by removing, clearing cache, and reinstalling
     * 
     * @return array Execution result
     */
    public function forceUpdateEpaphroditesPackage(): array
    {
        $this->displayMessage("🔄 Starting forced update of epaphrodites/packages...", 'magenta');
        
        $output = [];
        $success = true;

        $this->displayMessage("🗂️  Step 1/3: Removing package from composer.json", 'yellow');
        if (!$this->removePackageFromComposer()) {
            $output[] = "Failed to remove package from composer.json";
            $success = false;
        }

        $this->displayMessage("🧹 Step 2/3: Clearing Composer cache...", 'yellow');
        $this->displayWaitingAnimation("Clearing cache", 2);
        
        $clearCommand = 'composer clear-cache';
        $clearOutput = [];
        $clearReturnCode = 0;
        exec($clearCommand . ' 2>&1', $clearOutput, $clearReturnCode);

        if ($this->verbose) {
            foreach ($clearOutput as $line) {
                $this->displayMessage("  " . $line, 'cyan');
            }
        }

        if ($clearReturnCode !== 0) {
            $output[] = "Failed to clear Composer cache";
            $this->displayMessage("❌ Failed to clear Composer cache", 'red');
            $success = false;
        } else {
            $output[] = "Composer cache cleared successfully";
            $this->displayMessage("✅ Composer cache cleared successfully", 'green');
        }

        $this->displayMessage("📦 Step 3/3: Reinstalling package", 'yellow');
        $installResult = $this->updateEpaphroditesPackage();
        $output = array_merge($output, $installResult['output']);

        if (!$installResult['success']) {
            $success = false;
        }

        if ($success) {
            $this->displayMessage("🎊 Forced update completed successfully!", 'green');
        } else {
            $this->displayMessage("❌ Forced update failed", 'red');
        }

        return [
            'success' => $success,
            'output' => $output,
            'return_code' => $installResult['return_code']
        ];
    }
}