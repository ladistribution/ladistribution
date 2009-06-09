<?php

require_once 'Ld/Controller/Action.php';

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    function loginAction()
    {
        $this->view->postParams = $_POST;

        if ($this->getRequest()->isPost() && Zend_Auth::getInstance()->hasIdentity()) {
            $this->_forward('index', 'index', 'default');
        }
    }

    function disallowAction()
    {
    }

}
