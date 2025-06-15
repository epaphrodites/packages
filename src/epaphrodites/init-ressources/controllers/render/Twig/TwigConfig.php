<?php

namespace Epaphrodites\controllers\render\Twig;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Epaphrodites\epaphrodites\env\config\ResponseSequence;
use Epaphrodites\epaphrodites\define\config\traits\currentFunctionNamespaces;
use Epaphrodites\epaphrodites\define\config\traits\currentVariableNameSpaces;

class TwigConfig extends ResponseSequence{

    use currentVariableNameSpaces , currentFunctionNamespaces;

    /**
     * Twig path Environment
     * @var \Twig\Environment $twigEnvironment
     * @return mixed
    */    
    private function getTwigEnvironement(): Environment
    {

        $twigEnvironment = new Environment ( (new FilesystemLoader ( _DIR_VIEWS_ ) ) , [ 'cache' =>false ]);
        
        $twigEnvironment->addExtension(static::initConfig()['extension']);

        return $twigEnvironment;
    }

    /**
     * Get Twig Environment instance
     * 
     * @return \Twig\Environment
     */    
    public function getTwigEnvironmentInstance(): Environment
    {

        return $this->getTwigEnvironement();
    }
}