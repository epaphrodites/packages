<?php

namespace Epaphrodites\database\requests\mainRequest\select;

use Epaphrodites\database\requests\typeRequest\sqlRequest\select\general as GeneralGeneral;

final class general extends GeneralGeneral
{

  /**
   * Get all users history actions
   * @return array
   */
  public function usersHistory(): array
  {
    return match (_FIRST_DRIVER_) {

      'mongodb' => $this->mongodbUsersHistoryActions(),
      'redis' => $this->redisUsersHistoryActions(),
      'oracle' => $this->oracleHistoryRequest(),
      'sqlserver' => $this->sqlSeverHistoryRequest(),

      default => $this->defaultSqlHistoryRequest(),
    };
  }
}