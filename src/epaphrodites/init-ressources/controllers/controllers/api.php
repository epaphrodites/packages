<?php

namespace Epaphrodites\controllers\controllers;

use Epaphrodites\epaphrodites\heredia\HerediaApiSwitcher;
use Epaphrodites\epaphrodites\env\config\ResponseSequence;

final class api extends HerediaApiSwitcher
{

    protected object $Response;

    /**
     * Initialize object properties when an instance is created
     * 
     * @return void
     */    
    public final function __construct()
    {
        $this->initializeObjects();
    }

    /**
     * Initialize each property using values retrieved from static configurations
     * @return void
     */
    private function initializeObjects():void
    {
        $this->Response = new ResponseSequence;
    }      

    /**
     * make api test
     */
    public final function makeApiTest()
    {

        $Result = [];
        $code = 400;
        if (static::isValidApiMethod(true)) {

            $code = 200;
            $Result = ['this' , 'is' , 'api' , 'result' , 'test']; 
        }

        return $this->Response->JsonResponse($code, $Result);
    }   
}