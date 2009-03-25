<?php

require_once 'BaseController.php';

/**
 * Users controller
 */
class UsersController extends BaseController
{
    
    public function indexAction()
    {
        $this->view->users = $this->site->getUsers();
    }
    
    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $user = array(
                'username'   => $this->_getParam('username'),
                'password'   => $this->_getParam('password'),
                'screenname' => $this->_getParam('screenname'),
                'email'      => $this->_getParam('email')
            );
            $this->site->addUser($user);
            $this->_forward('index');
        }
    }
}
