<?php

namespace Epaphrodites\controllers\switchers;

use Epaphrodites\epaphrodites\heredia\SettingHeredia;
use Epaphrodites\epaphrodites\heredia\SwitchersHeredia;
use Epaphrodites\epaphrodites\define\config\traits\currentSubmit;
use Epaphrodites\epaphrodites\define\config\traits\currentResponses;

class MainSwitchers extends SwitchersHeredia
{

    use currentSubmit, currentResponses;

    /**
     * Rooter constructor
     *
     * @return \Epaphrodites\controllers\render\rooter
     */
    public static function rooter(): \Epaphrodites\controllers\render\rooter
    {
        return new \Epaphrodites\controllers\render\rooter(new SettingHeredia);
    }

    /**
     * @param string $html
     * @param array $content
     * @param bool $autorize
     * @return void
     */
    public function views( 
        string $html , 
        array $content = [] , 
        bool $autorize = false 
    ):void{
        
        static::rooter()->target($html)->content($content , $autorize)->get();
    }
}
