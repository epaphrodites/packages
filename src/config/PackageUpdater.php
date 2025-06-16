<?php

namespace Epaphrodites\Packages\config;

/**
 * Class for managing epaphrodites/packages package updates
 */
class PackageUpdater
{
    private $verbose;

    /**
     * Constructor
     * 
     * @param bool $verbose Show detailed output (default false)
     */
    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
    }

    /**
     * Updates the epaphrodites/packages package via Composer
     * 
     * @return array Execution result
     */
    public function updateEpaphroditesPackage()
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
}

?>