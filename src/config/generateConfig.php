<?php

namespace Epaphrodites\Packages\config;

use Epaphrodites\Packages\config\EpaphroditesConfigReader;

class generateConfig
{

    public function readYamlFile(){

        $rootDir = getcwd();
        $reader = new EpaphroditesConfigReader($rootDir.'synchrone-config.yaml');

        $reader->isUpdateTypeEnabled('all');

    }

    public static function lunch()
    {
        $instance = new self();
        $instance->readYamlFile();
    }

}