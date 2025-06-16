<?php

namespace Epaphrodites\Packages;

use Epaphrodites\Packages\EpaphroditesConfigReader;

class generateConfig
{

    public function readYamlFile(){

        $rootDir = getcwd();
        $reader = new EpaphroditesConfigReader($rootDir.'epaphrodites-config.yaml');

        $reader->isUpdateTypeEnabled('all');

    }

    public static function lunch()
    {
        $instance = new self();
        $instance->readYamlFile();
    }

}