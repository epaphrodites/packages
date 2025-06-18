<?php

namespace Epaphrodites\database\requests\typeRequest\sqlRequest\select;

use Epaphrodites\database\requests\typeRequest\noSqlRequest\select\count as SelectCount;

class count extends SelectCount
{

  /**
   * Count all database users
   * 
   * @return int
   */
  public function sqlCountAllUsers(): int
  {

    $result = $this->table('usersaccount')
                  ->SQuery("COUNT(*) AS nbre");

    $result = static::initNamespace()['env']->dictKeyToLowers($result);

    return $result[0]['nbre'];
  }

  /** 
   * Count all database users by group
   * 
   * @param int $Group
   * @return int
   */
  public function sqlCountUsersByGroup(
    int|string $Group
  ): int
  {

    $result = $this->table('usersaccount')
                  ->where('usersgroup')
                  ->param([$Group])
                  ->SQuery("COUNT(*) AS nbre");

    $result = static::initNamespace()['env']->dictKeyToLowers($result);

    return $result[0]['nbre'];
  }

  /** 
   * Count all database history
   * 
   * @param int $Group
   * @return int
   */
  public function sqlCountUsersRecentActions(): int
  {
    
    $result = $this->table('history')
                  ->SQuery("COUNT(*) AS nbre");

    $result = static::initNamespace()['env']->dictKeyToLowers($result);  

    return $result[0]['nbre'];
  }     
}