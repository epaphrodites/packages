<?php

namespace Epaphrodites\controllers\controllers;

use Epaphrodites\controllers\switchers\MainSwitchers;

final class setting extends MainSwitchers
{
    
    private object $msg;
    private object $env;
    private object $data;
    private object $count;
    private object $getId;
    private object $mozart;
    private object $update;
    private object $select;
    private object $insert;
    private object $delete;
    private string $alert = '';
    private string $ans = '';
    private array|bool $result = [];

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
     * 
     * @return void
     */
    private function initializeObjects(): void
    {
        $this->msg = $this->getFunctionObject(static::initNamespace(), 'msg');
        $this->env = $this->getFunctionObject(static::initNamespace(), 'env');
        $this->getId = $this->getFunctionObject(static::initQuery(), 'getid');
        $this->count = $this->getFunctionObject(static::initQuery(), 'count');
        $this->select = $this->getFunctionObject(static::initQuery(), 'select');
        $this->insert = $this->getFunctionObject(static::initQuery(), 'insert');
        $this->delete = $this->getFunctionObject(static::initQuery(), 'delete');
        $this->update = $this->getFunctionObject(static::initQuery(), 'update');
        $this->data = $this->getFunctionObject(static::initNamespace(), 'datas');
        $this->mozart = $this->getFunctionObject(static::initNamespace(), 'mozart');
    }      

    /**
     * Adds user access rights.
     *
     * @param string $html
     * @return void
     */
    public final function assignUserAccessRights(
        string $html
    ): void
    {

        $usersGroup = static::isGet('_see', 'int') ? static::getGet('_see') : 0;

        if (static::isValidMethod(true) && $usersGroup !== 0) {

            if(static::isArray('__rights__')){

                $allSelectedPages = static::isArray('__rights__');

                foreach( $allSelectedPages as $pageSelected){

                    $this->result = $this->insert->AddUsersRights($usersGroup, $pageSelected, static::getPost('__actions__'));
                }

                [$this->ans, $this->alert] = static::Responses(
                    $this->result, [  
                        true => ['succes', 'alert-success'], 
                        false => ['rightexist', 'alert-danger'] 
                    ]);
            }
        }

        $usersSelected = $usersGroup !== 0 ? $this->data->usersGroup($usersGroup, 'label') : 'ERROR';

        $this->views( $html, 
            [
                'data' => $this->data,
                'reponse' => $this->ans,
                'alert' => $this->alert,
                'select' => $this->mozart,
                'usersSelected' => $usersSelected,
            ],
            true
        );
    }

    /**
     * Lists all user groups with their rights.
     *
     * @param string $html
     * @return void
     */
    public final function listOfUserRightsManagement(
        string $html
    ): void
    {

        $usersGroup = static::isGet('_see', 'int') ? static::getGet('_see') : 0;

        if (static::isValidMethod(true)&&static::arrayNoEmpty(['group'])) {

            // Authorize user right
            if (static::isSelected('_sendselected_', 1 )) {

                foreach (static::isArray('group') as $UsersGroupSelect ) {

                    $this->result = $this->update->updateUserRights($UsersGroupSelect, 1, $usersGroup);
                }

                [$this->ans, $this->alert] = static::Responses(
                    $this->result, [  
                        true => ['succes', 'alert-success'], 
                        false => ['error', 'alert-danger'] 
                    ]);
            }

            // Refuse user right
            if (static::isSelected('_sendselected_', 2 )) {

                foreach (static::isArray('group') as $usersGroupSelected) {

                    $this->result = $this->update->updateUserRights($usersGroupSelected, 0, $usersGroup);
                }

                [$this->ans, $this->alert] = static::Responses(
                    $this->result, [  
                        true => ['succes', 'alert-success'], 
                        false => ['error', 'alert-danger'] 
                    ]);                
            }

            // Deleted user right
            if (static::isSelected('_sendselected_', 3 )) {

                foreach (static::isArray('group') as $usersGroupSelected) {

                    $this->result = $this->delete->DeletedUsersRights($usersGroupSelected, $usersGroup);
                }

                [$this->ans, $this->alert] = static::Responses(
                    $this->result, [  
                        true => ['succes', 'alert-success'], 
                        false => ['error', 'alert-danger'] 
                    ]);                
            }
        }

        $this->views( $html, 
            [
                'reponse' => $this->ans,
                'alert' => $this->alert,
                'list' => $this->mozart,
                'select' => $this->getId->getUsersRights($usersGroup),
            ],
            true
        );
    }

    /**
     * Manages user access rights per group.
     *
     * @param string $html
     * @return void
     */
    public final function managementOfUserAccessRights(
        string $html
    ): void
    {

        if (static::isValidMethod(true)) {

            if (static::isPost('__deleted__')) {

                $this->result = $this->delete->EmptyAllUsersRights(static::getPost('__deleted__'));

                [$this->ans, $this->alert] = static::Responses(
                    $this->result, [  
                        true => ['succes', 'alert-success'], 
                        false => ['error', 'alert-danger'] 
                    ]);
            }
        }

        $this->views( $html, 
            [
                'select' => $this->data->usersGroup(),
                'auth' => static::class('session'),
                'reponse' => $this->ans,
                'alert' => $this->alert
            ],
            true
        );
    }

    /**
     * Manages user dashboard colors
     *
     * @param string $html
     * @return void
     */
    public final function managementOfUserColors(
        string $html
    ): void
    {

        if (static::isValidMethod(true)&&static::arrayNoEmpty(['group'])) {

            if (static::isSelected('_sendselected_', 1 )) {

                foreach (static::isArray('group') as $UsersGroup) {

                    $colors = static::getPost("{$UsersGroup}colors", true)[0];

                    $this->result = $this->insert->setDashboardColors($UsersGroup, $colors);
                }

                [ $this->ans, $this->alert ] = static::Responses(
                    $this->result, [  
                        true => ['succes', 'alert-success'], 
                        false => ['error', 'alert-danger'] 
                    ]);
            }
        }

        $this->views( $html, 
            [
                'selectColors' => $this->select->selectUsersColors(),
                'reponse' => $this->ans,
                'alert' => $this->alert
            ],
            true
        );
    }    

    /**
     * List of recent actions
     * @param string $html
     * @return void
     */
    public final function listOfRecentActions(
        string $html
    ): void
    {
        $total = 0;
        $list = [];
        $numLine = 100;
        $currentPage = static::isGet('_p', 'int') ? static::getGet('_p') : 1;
        $position = static::notEmpty(['filtre'] , 'GET') ? static::getGet('filtre') : NULL;

        if (static::isGet('submitsearch') && static::notEmpty(['datasearch'] , 'GET')) {

            $list = $this->getId->getUsersRecentsActions(static::getGet('datasearch'));
            $total = count($list ?? []);
        } else {

            $total = $this->count->countUsersRecentActions();
            $list = $this->select->listOfRecentActions($currentPage, $numLine);
        }

        $this->views( $html, 
            [
                'current' => $currentPage,
                'total' => $total,
                'liste_users' => $list,
                'reponse' => $this->ans,
                'alert' => $this->alert,
                'position' => $position,
                'nbrePage' => ceil(($total) / $numLine),
                'select' => $this->getId,
            ],
            true
        );
    }     
}