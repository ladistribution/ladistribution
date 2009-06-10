<?php

require_once 'Ld/Controller/Action.php';

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    function loginAction()
    {
        $this->view->postParams = array();
        foreach ($_POST as $key => $value) {
            $ignore = array('ld_auth_username', 'ld_auth_password', 'ld_auth_action');
            if (in_array($key, $ignore)) {
                continue;
            }
            $this->view->postParams[$key] = $value;
        }

        if ($this->getRequest()->isPost() && Zend_Auth::getInstance()->hasIdentity()) {
            $this->_forward('index', 'index', 'default');
        }
    }

    function disallowAction()
    {
    }

}
