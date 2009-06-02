<?php

require_once 'BaseController.php';

/**
 * Users controller
 */
class Slotter_UsersController extends BaseController
{

    public function indexAction()
    {
        $applications = $this->site->getInstances('application');

        // if ($this->getRequest()->isPost()) {
        //     $roles = $this->_getParam('roles');
        //     foreach ($applications as $id => $application) {
        //         $path = $application['path'];
        //         if (isset($roles[$path])) {
        //             $instance = $this->site->getInstance($path);
        //             $instance->setUserRoles($roles[$path]);
        //         }
        //     }
        // }

        $this->view->users = $this->site->getUsers();

        // foreach ($applications as $id => $application) {
        //     $instance = $this->site->getInstance($application['path']);
        //     $applications[$id]['roles'] = $instance->getRoles();
        //     $applications[$id]['userRoles'] = $instance->getUserRoles();
        // }
        // 
        // $this->view->applications = $applications;
    }

    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $user = array(
                'username'   => $this->_getParam('username'),
                'password'   => $this->_getParam('password'),
                'fullname'   => $this->_getParam('fullname'),
                'email'      => $this->_getParam('email')
            );
            $this->site->addUser($user);
            $this->_redirector->gotoSimple('index', 'users');
        }
    }

    public function deleteAction()
    {
        $this->view->username = $id = $this->_getParam('id');
        if ($this->getRequest()->isPost()) {
            $this->site->deleteUser($id);
            $this->_redirector->gotoSimple('index', 'users');
        }
    }

}
