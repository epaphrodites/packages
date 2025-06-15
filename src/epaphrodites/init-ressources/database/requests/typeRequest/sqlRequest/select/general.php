<?php

namespace Epaphrodites\database\requests\typeRequest\sqlRequest\select;

use Epaphrodites\database\requests\typeRequest\noSqlRequest\select\general as SelectGeneral;

class general extends SelectGeneral
{

    /**
     * Request select last users history actions (For: sqlServer)
     * 
     * @return array
     */
    public function sqlSeverHistoryRequest():array
    {
       
        $UserConnected = static::initNamespace()['session']->login();

        $result = $this->table('history')
            ->like('actions')
            ->orderBy('_id', 'DESC')
            ->offset(0,6)
            ->param([$UserConnected])
            ->SQuery();
       
        return $result;
    }

    /**
     * Request select last users history actions (For: oracle )
     * 
     * @return array
     */
    public function oracleHistoryRequest():array
    {
       
        $UserConnected = static::initNamespace()['session']->login();
       
        $result = $this->table('history')
            ->like('actions')
            ->orderBy('"_id"', 'DESC')
            ->offset(0,6)
            ->param([$UserConnected])
            ->SQuery();
            
        return static::initNamespace()['env']->dictKeyToLowers($result);
    }    

    /**
     * Request select last users history actions (For: Mysql, postgres, sqLite)
     * 
     * @return array
     */
    public function defaultSqlHistoryRequest():array
    {
       
        $UserConnected = static::initNamespace()['session']->login();

        $result = $this->table('history')
            ->like('actions')
            ->orderBy('_id', 'DESC')
            ->limit(0,6)
            ->param([$UserConnected])
            ->SQuery();
       
        return $result;
    }    
}