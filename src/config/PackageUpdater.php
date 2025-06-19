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
    public function __construct(bool $verbose = false, string $composerFile = './composer.json')
    {
        $this->verbose = $verbose;
        $this->composerFile = $composerFile;
    }

    /**
     * Removes epaphrodites/packages from composer.json
     * 
     * @return bool Success status
     */
    public function removePackageFromComposer(): bool
    {
        try {
            if (!file_exists($this->composerFile)) {
                throw new Exception("composer.json not found at: {$this->composerFile}");
            }

            $composerData = json_decode(file_get_contents($this->composerFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid composer.json format");
            }

            // Remove from require section
            if (isset($composerData['require']['epaphrodites/packages'])) {
                unset($composerData['require']['epaphrodites/packages']);
            }

            // Remove from require-dev section
            if (isset($composerData['require-dev']['epaphrodites/packages'])) {
                unset($composerData['require-dev']['epaphrodites/packages']);
            }

            // Write updated composer.json
            $result = file_put_contents(
                $this->composerFile,
                json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            if ($this->verbose) {
                echo $result !== false
                    ? "Successfully removed epaphrodites/packages from {$this->composerFile}\n"
                    : "Failed to remove epaphrodites/packages from {$this->composerFile}\n";
            }

            return $result !== false;
        } catch (Exception $e) {
            if ($this->verbose) {
                echo "Error removing package: {$e->getMessage()}\n";
            }
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
        $command = 'composer require epaphrodites/packages';
        $output = [];
        $returnCode = 0;

        exec($command . ' 2>&1', $output, $returnCode);

        if ($this->verbose) {
            echo implode("\n", $output) . "\n";
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
        $output = [];
        $success = true;

        // Step 1: Remove package from composer.json
        if (!$this->removePackageFromComposer()) {
            $output[] = "Failed to remove package from composer.json";
            $success = false;
        }

        // Step 2: Clear Composer cache
        $clearCommand = 'composer clear-cache';
        $clearOutput = [];
        $clearReturnCode = 0;
        exec($clearCommand . ' 2>&1', $clearOutput, $clearReturnCode);

        if ($this->verbose) {
            echo implode("\n", $clearOutput) . "\n";
        }

        if ($clearReturnCode !== 0) {
            $output[] = "Failed to clear Composer cache";
            $success = false;
        } else {
            $output[] = "Composer cache cleared successfully";
        }

        // Step 3: Reinstall package
        $installResult = $this->updateEpaphroditesPackage();
        $output = array_merge($output, $installResult['output']);

        if (!$installResult['success']) {
            $success = false;
        }

        return [
            'success' => $success,
            'output' => $output,
            'return_code' => $installResult['return_code']
        ];
    }
}