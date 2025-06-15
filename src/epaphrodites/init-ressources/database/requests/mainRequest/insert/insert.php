<?php

namespace Epaphrodites\database\requests\mainRequest\insert;

use Epaphrodites\database\requests\typeRequest\sqlRequest\insert\insert as InsertInsert;

final class insert extends InsertInsert
{

    /**
     * Add users rights
     * 
     * @param int|null $usersGroup
     * @param string|null $pages
     * @param string|null $actions
     * @return bool
     */
    public function AddUsersRights(
      int|null $usersGroup = null, 
      string|null $pages = null, 
      string|null  $actions = null
    ):bool{

      if (static::initConfig()['addright']->AddUsersRights($usersGroup, $pages, $actions) === true) {
        
            $config = static::initQuery()['setting'];
            $actions = "Assign a right to the user group : " . static::initNamespace()['datas']->usersGroup($usersGroup, 'label');

            match (_FIRST_DRIVER_) {

              'mongodb' => $config->noSqlActionsRecente($actions),
              'redis' => $config->noSqlRedisActionsRecente($actions),
        
              default => $config->ActionsRecente($actions),
            };

            return true;
        } else {
            return false;
        }
    }  

    /**
     * Set user dashboard color
     * 
     * @param int $usersGroup
     * @param string $color
     * @return bool
     */
    public function setDashboardColors(
      int $usersGroup, 
      string $color
    ): bool{
        if (empty($usersGroup) || empty($color)) {
            return false;
        }

        $json = static::initNamespace()['json'];
        $path = $json->path(_DIR_COLORS_PATH_);

        $existingEntry = $path->get(['usersGroup' => $usersGroup]);

        if (!$existingEntry) {
            $path->add(['usersGroup' => $usersGroup, 'color' => $color]);
        } else {
            $path->where(['usersGroup' => $usersGroup])->update(['color' => $color]);
        }

        return true;
    }  

  /**
   * Add a new users
   * @param string $login
   * @param int $usersGroup
   * @return bool
   */
  public function addUsers(
    string $login, 
    int $usersGroup
  ):bool{

    return match (_FIRST_DRIVER_) {

      'mongodb' => $this->noSqladdUsers($login , $usersGroup),
      'redis' => $this->noSqlRedisAddUsers($login , $usersGroup),

      default => $this->sqlAddUsers($login , $usersGroup),
    };    
  } 
  
   /**
   * Add a new users from console
   * @param string $login
   * @param string $password
   * @param int $usersGroup
   * @return array
   */
  public function ConsoleAddUsers(
    string $login,
    string $password,
    int $usersGroup
  ):bool{

    return match (_FIRST_DRIVER_) {

      'mongodb' => $this->noSqlConsoleAddUsers($login , $password, $usersGroup),
      'redis' => $this->noSqlRedisConsoleAddUsers($login , $password, $usersGroup),

      default => $this->sqlConsoleAddUsers($login , $password, $usersGroup),
    };     
  }  

 }