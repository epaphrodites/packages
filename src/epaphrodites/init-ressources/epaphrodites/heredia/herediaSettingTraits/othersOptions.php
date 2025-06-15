<?php

namespace Epaphrodites\epaphrodites\heredia\herediaSettingTraits;

trait othersOptions{

    /**
     * Set others options
     * 
     * @return array
     */
    public function others_options(): array
    {
        return
            [

                /*
            |--------------------------------------------------------------------------
            | Secure session
            |--------------------------------------------------------------------------
            |
            | Supported: "true", "false"
            |
            */
                'secure' => true
            ];
    }   
    
    /**
     * Users dashboard color
     * 
     * @return string
     */
    public function getUsersDashboardColor(): string
    {
        return $this->json
            ->path(_DIR_COLORS_PATH_)
            ->get(['usersGroup' => $this->session->type()])[0]['color'] ?? 'main';
    }    
}