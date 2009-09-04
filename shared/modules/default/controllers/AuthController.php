<?php

require_once 'Ld/Controller/Action.php';

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    function init()
    {
        parent::init();

        $this->_setTitle('La Distribution');
    }

    function loginAction()
    {
        if (Ld_Auth::isAuthenticated() && Ld_Auth::isAnonymous()) {
            $this->_forward('complete');
            return;
        }

        $this->appendTitle('Login');

        $this->view->postParams = array();
        foreach ($_POST as $key => $value) {
            $ignore = array(
                'ld_auth_username', 'ld_auth_password', 'ld_auth_action',
                'ld_referer',
                'openid_action', 'openid_identifier');
            if (in_array($key, $ignore)) {
                continue;
            }
            $this->view->postParams[$key] = $value;
        }

        $this->view->referer = $this->_getReferer();

        if ($this->_hasParam('ld_auth_username')) {
            $this->view->ld_auth_user = $this->getSite()->getUser($this->_getParam('ld_auth_username'));
            if (empty($this->view->ld_auth_user)) {
                $this->view->authentication = new Zend_Auth_Result(
                    Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                    $this->_getParam('ld_auth_username'), 
                    array("Identity not found."));
            }
        }

        if (Ld_Auth::isAuthenticated()) {
            if ($this->getRequest()->isPost() || $this->_hasParam('openid_mode')) {

                $session = new Zend_Session_Namespace("ld_openid");

                $this->_tryRedirect(array(
                    $this->_getParam('ld_referer'),
                    $session->referer,
                    $this->view->url(array(), null, true)
                ));

            }
        }

        $this->view->open_registration = $this->getSite()->getConfig('open_registration');
    }

    function logoutAction()
    {
        Ld_Auth::logout();

        $this->_redirectToRefererOrRoot();
    }

    function registerAction()
    {
        $this->appendTitle('Register');

        $open_registration = $this->getSite()->getConfig('open_registration');
        if (empty($open_registration)) {
            throw new Exception('Registration is closed.');
        }

        if ($this->getRequest()->isPost()) {

            $user = array(
                'username'   => $this->_getParam('ld_register_username'),
                'password'   => $this->_getParam('ld_register_password'),
                'email'      => $this->_getParam('ld_register_email')
            );

            if (Ld_Auth::isOpenid()) {
                $user['identities'] = array( Ld_Auth::getIdentity() );
                if (!$this->_hasParam('ld_register_password')) {
                    $this->_setParam('ld_register_password', Ld_Auth::generatePhrase(8));
                }
            }

            try {

                $this->getSite()->addUser($user);

                if (!Ld_Auth::isOpenid()) {
                    Ld_Auth::authenticate($this->_getParam('ld_register_username'), $this->_getParam('ld_register_password'));
                }

                $this->_redirectToRefererOrRoot();

            } catch (Exception $e) {

                $this->view->user = $user;

                $this->view->error = $e->getMessage();

            }

        }
    }

    function completeAction()
    {
        if (!Ld_Auth::isAuthenticated()) {
            $this->_forward('disallow');
            return true;
        }

        if (!Ld_Auth::isAnonymous()) {
            $this->_redirector->goto('index', 'index', 'default');
            return true;
        }

        $this->view->complete = true;

        if ($this->getRequest()->isPost()) {

            $this->_forward('register');

        } elseif ($this->getRequest()->isGet()) {

            $this->view->user = $user = array(
                'username'   => $this->_getParam('openid_sreg_nickname'),
                'fullname'   => $this->_getParam('openid_sreg_fullname'),
                'email'      => $this->_getParam('openid_sreg_email')
            );

            $this->render('register');

        }
    }

    function disallowAction()
    {
    }

    function _getReferer($auto = true)
    {
        if ($this->_hasParam('ld_referer')) {
            return $this->_getParam('ld_referer');
        }

        if ($auto) {
            $referer = $this->getRequest()->getServer('HTTP_REFERER');
            if (false === strpos($referer, 'auth/login')) {
                return $referer;
            }
        }
    }

    function _redirectToRefererOrRoot($auto = true)
    {
        $this->_tryRedirect(array(
            $this->_getReferer($auto),
            $this->view->url(array(), null, true)
        ));
    }

    function _tryRedirect(array $urls = array())
    {
        foreach ($urls as $url) {
            if (Zend_Uri_Http::check($url)) {
                return $this->_redirect($url);
            }
        }
    }

}
