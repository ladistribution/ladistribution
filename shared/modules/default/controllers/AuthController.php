<?php

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    function loginAction()
    {
        if (Ld_Auth::isAuthenticated() && Ld_Auth::isOpenid() && Ld_Auth::isAnonymous()) {
            $session = new Zend_Session_Namespace("ld_openid");
            $session->username = $this->_getParam('openid_sreg_nickname');
            $session->fullname = $this->_getParam('openid_sreg_fullname');
            $session->email = $this->_getParam('openid_sreg_email');
            $this->_redirector->goto('complete', 'auth', 'default');
            return;
        }

        $translator = $this->getTranslator();
        $this->appendTitle( $translator->translate('Log In') );

        $this->view->postParams = array();
        foreach ($_POST as $key => $value) {
            $ignore = array(
                'ld_auth_username', 'ld_auth_password', 'ld_auth_action', 'ld_auth_remember',
                'ld_referer',
                'openid_action', 'openid_identifier');
            if (in_array($key, $ignore)) {
                continue;
            }
            $this->view->postParams[$key] = $value;
        }

        // we register the referer in the session and pass to the view
        $this->_session = new Zend_Session_Namespace("ld_auth");
        $this->view->referer = $this->_session->referer = $this->_getReferer();

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

            } else {
                $this->_redirector->goto('index', 'index', 'default');
            }
        }

        $this->view->identities = Ld_Auth::getIdentities();

        $this->view->open_registration = $this->getSite()->getConfig('open_registration');
    }

    function logoutAction()
    {
        Ld_Auth::logout();

        $this->_redirectToRefererOrRoot();
    }

    function registerAction()
    {
        $translator = $this->getTranslator();

        $this->appendTitle( $translator->translate('Register') );

        // get the referer
        $this->view->referer = $this->_getReferer();
        if (empty($this->view->referer)) {
            $this->_session = new Zend_Session_Namespace("ld_auth");
            if (isset($this->_session->referer)) {
                $this->view->referer = $this->_session->referer;
            }
        }

        $open_registration = $this->getSite()->getConfig('open_registration');
        if (empty($open_registration)) {
            $roles = $this->admin->getUserRoles();
            if (!$this->getSite()->isChild() && empty($roles)) {
                // skip exception
            } elseif ($this->_hasParam('token')) {
                // skip exception
            } else {
                throw new Exception( $translator->translate('Registration is closed.') );
            }
        }

        if ($this->getRequest()->isPost()) {

            try {

                $user = array(
                    'origin'           => 'Auth:register',
                    'agent'            => $this->getRequest()->getServer('HTTP_USER_AGENT'),
                    'ip'               => $this->getRequest()->getServer('REMOTE_ADDR'),
                    'username'         => trim($this->_getParam('ld_register_username', '')),
                    'password'         => trim($this->_getParam('ld_register_password', '')),
                    'password_again'   => trim($this->_getParam('ld_register_password_again', '')),
                    'email'            => trim($this->_getParam('ld_register_email', ''))
                );

                if (Ld_Auth::isOpenid()) {
                    $user['identities'] = array( Ld_Auth::getIdentity() );
                    if (empty($user['password'])) {
                        $user['password'] = $user['password_again'] = Ld_Auth::generatePhrase(8);
                    }
                }

                // Basic validation
                if (empty($user['username'])) {
                    throw new Exception( $translator->translate("Username can't be empty.") );
                } else if (empty($user['email'])) {
                    throw new Exception( $translator->translate("Email can't be empty.") );
                } else if (empty($user['password']) && empty($user['password_again'])) {
                    throw new Exception( $translator->translate("Password can't be empty.") );
                } else if ($user['password'] != $user['password_again']) {
                    throw new Exception( $translator->translate("Passwords must match.") );
                }

                Ld_Plugin::doAction('Auth:register:validate', $this->_getAllParams());

                unset($user['password_again']);
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

    function activateAction()
    {
        $translator = $this->getTranslator();

        if (!$this->_hasParam('token')) {
            throw new Exception("No token given.");
        }

        $user = $this->getUsersBackend()->getUserBy('token', $this->_getParam('token'));
        if (empty($user)) {
            throw new Exception("Invalid token");
        }

        $username = $user['username'];

        if ($this->getRequest()->isGet()) {

            $this->view->user = $user;
            $this->view->finish = true;
            $this->render('register');

        } elseif ($this->getRequest()->isPost()) {

            try {

                // Update Username
                $user['username'] = $this->_getParam('ld_register_username');

                // Update Password
                $password = $this->_getParam('ld_register_password');
                $password_again = $this->_getParam('ld_register_password_again');
                if (empty($password) && empty($password_again)) {
                    throw new Exception( $translator->translate("Password can't be empty.") );
                } else if ($password != $password_again) {
                    throw new Exception( $translator->translate("Passwords must match.") );
                }
                $user['password'] = $password;

                // Empty token
                $user['token'] = '';

                // Activated
                $user['activated'] = true;

                $this->site->updateUser($username, $user);

                // Authenticate with credentials, and remember
                Ld_Auth::rememberIdentity($user['username']);
                Ld_Auth::authenticate($user['username'], $password);

                // Redirect
                $this->noRender();
                $this->_redirect( $this->getSite()->getBaseUrl() );

            } catch (Exception $e) {

                $this->view->user = $user;
                $this->view->error = $e->getMessage();
                $this->view->finish = true;
                $this->render('register');
            }

        }
    }

    function completeAction()
    {
        if (!Ld_Auth::isAuthenticated()) {
            $this->_redirector->goto('login', 'auth', 'default');
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

            $session = new Zend_Session_Namespace("ld_openid");

            $this->view->user = $user = array(
                'username'   => $session->username,
                'fullname'   => $session->fullname,
                'email'      => $session->email
            );

            $this->render('register');

        }
    }

    function disallowAction()
    {
    }

    function _getReferer($auto = true)
    {
        // don't try to redirect to referer is this an OpenID request
        if ($this->_hasParam('openid_mode') || $this->_getParam('openid_identity') || $this->_getParam('openid_assoc_handle')) {
            return null;
        }

        if ($this->_hasParam('ld_referer')) {
            return $this->_getParam('ld_referer');
        }

        if ($auto) {
            $referer = $this->getRequest()->getServer('HTTP_REFERER');
            if (false === strpos($referer, 'auth/login') && false === strpos($referer, 'auth/register')) {
                return $referer;
            }
        }
    }

    function _redirectToRefererOrRoot($auto = true)
    {
        $this->_tryRedirect(array(
            $this->_getReferer($auto),
            $this->getSite()->getBaseUrl()
        ));
    }

    function _tryRedirect(array $urls = array())
    {
        foreach ($urls as $url) {
            if (Zend_Uri_Http::check($url)) {
                $this->noRender();
                $this->_redirect($url);
                return;
            }
        }
        throw new Exception("Can't redirect.");
    }

}
