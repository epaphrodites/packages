<?php

use Ndri\config\EpaphroditesConfigReader;

class generateConfig
{

    public function readYamlFile(){

        $rootDir = getcwd();
        $reader = new EpaphroditesConfigReader($rootDir.'epaphrodites-config.yaml');

        $reader->isUpdateTypeEnabled('all');

        var_dump($reader);die;

    }

    public static function lunch()
    {
        $instance = new self();
        $instance->readYamlFile();
    }

}