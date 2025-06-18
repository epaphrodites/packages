<?php

namespace Epaphrodites\database\requests\typeRequest\sqlRequest\insert;

use Epaphrodites\database\requests\typeRequest\noSqlRequest\insert\insert as InsertInsert;

class insert extends InsertInsert
{

    /**
     * Add users to the system from the console
     *
     * @param string|null $login
     * @param string|null $password
     * @param int|null $usersGroup
     * @return bool
     */
    public function sqlConsoleAddUsers(
        string|null $login = null, 
        string|null $password = null, 
        int|null $usersGroup = null
    ):bool
    {

        $usersGroup = $usersGroup !== NULL ? $usersGroup : 1;

        if (!empty($login) && count(static::initQuery()['getid']->sqlGetUsersDatas($login)) < 1) {

            $this->table('usersaccount')
                ->insert(' login , password , usersgroup ')
                ->values(' ? , ? , ? ')
                ->param([static::initNamespace()['env']->no_space($login), static::initConfig()['guard']->CryptPassword($password), $usersGroup])
                ->IQuery();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add users to the system
     *
     * @param string|null $login
     * @param int|null $usersgroup
     * @return bool
     */
    public function sqlAddUsers(
        string|null $login = null, 
        int|null $usersgroup = null
    ):bool
    {

        if (!empty($login) && !empty($usersgroup) && count(static::initQuery()['getid']->sqlGetUsersDatas($login)) < 1) {

            $this->table('usersaccount')
                ->insert(' login , password , usersgroup ')
                ->values(' ? , ? , ? ')
                ->param([static::initNamespace()['env']->no_space($login), static::initConfig()['guard']->CryptPassword($login), $usersgroup])
                ->IQuery();

            $actions = "Add a User : " . $login;
            static::initQuery()['setting']->ActionsRecente($actions);

            return true;
        } else {
            return false;
        }
    }   
}