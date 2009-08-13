<?php

require_once 'Ld/Controller/Action.php';

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    function loginAction()
    {
        $this->_setTitle('Login');

        $this->view->postParams = array();
        foreach ($_POST as $key => $value) {
            $ignore = array('ld_auth_username', 'ld_auth_password', 'ld_auth_action');
            if (in_array($key, $ignore)) {
                continue;
            }
            $this->view->postParams[$key] = $value;
        }

        $this->view->referer = $this->_getReferer();

        if ($this->getRequest()->isPost() && Zend_Auth::getInstance()->hasIdentity()) {
            $this->_redirector->goto('index', 'index', 'default');
        }
    }

    function logoutAction()
    {
        Ld_Auth::logout();

        $referer = $this->_getReferer();

        if (isset($referer)) {
            $this->_redirect($referer);
        } else {
            $this->_redirect( Zend_Registry::get('site')->getUrl() );
        }
    }

    function registerAction()
    {
        $this->_setTitle('Register');

        if ($this->getRequest()->isPost()) {

            $user = array(
                'username'   => $this->_getParam('ld_register_username'),
                'password'   => $this->_getParam('ld_register_password'),
                'email'      => $this->_getParam('ld_register_email')
            );

            $site = Zend_Registry::get('site');
            $site->addUser($user);

            Ld_Auth::authenticate($this->_getParam('ld_register_username'), $this->_getParam('ld_register_password'));

            $this->_redirect( $site->getUrl() );

        }
    }

    function disallowAction()
    {
    }

    function _getReferer()
    {
        if ($this->_hasParam('ld_referer')) {
            return $this->_getParam('ld_referer');
        } else {
            return $this->getRequest()->getServer('HTTP_REFERER');
        }
    }

}
