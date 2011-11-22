<?php

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    public function preDispatch()
    {
        $this->view->noIndex = true;
    }

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

        $this->appendTitle( $this->translate('Sign In') );

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

        if (isset($_GET['forget-identity'])) {
            Ld_Auth::forgetIdentity($_GET['forget-identity']);
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
        $this->appendTitle( $this->translate('Sign Up') );

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
                throw new Exception( $this->translate('Registration is closed.') );
            }
        }

        if ($this->getRequest()->isPost()) {

            try {

                $user = array(
                    'origin'           => 'Auth:register',
                    'agent'            => $this->getRequest()->getServer('HTTP_USER_AGENT'),
                    'ip'               => $this->getRequest()->getServer('REMOTE_ADDR'),
                    'username'         => trim($this->_getParam('ld_register_username', '')),
                    'fullname'         => trim($this->_getParam('ld_register_fullname', '')),
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
                    throw new Exception( $this->translate("Username can't be empty.") );
                } else if (empty($user['email'])) {
                    throw new Exception( $this->translate("Email can't be empty.") );
                } else if (empty($user['password']) && empty($user['password_again'])) {
                    throw new Exception( $this->translate("Password can't be empty.") );
                } else if ($user['password'] != $user['password_again']) {
                    throw new Exception( $this->translate("Passwords must match.") );
                }

                Ld_Plugin::doAction('Auth:register:validate', $this->_getAllParams());

                unset($user['password_again']);
                $this->getSite()->addUser($user);

                if (!Ld_Auth::isOpenid()) {
                    Ld_Auth::rememberIdentity($user['username']);
                    Ld_Auth::authenticate($user['username'], $user['password']);
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
        if (!$this->_hasParam('token')) {
            throw new Exception("No token given.");
        }

        $tokenUser = $this->site->getModel('users')->getUserBy('token', $this->_getParam('token'));
        if (empty($tokenUser)) {
            throw new Exception("Invalid token");
        }

        $tokenUsername = $tokenUser['username'];

        if ($this->getRequest()->isGet()) {

            $this->view->user = $tokenUser;
            $this->view->finish = true;
            $this->render('register');

        } elseif ($this->getRequest()->isPost()) {

            try {

                $user = $tokenUser;
                $user['username'] = $username = trim($this->_getParam('ld_register_username', ''));
                $user['fullname'] = $fullname = trim($this->_getParam('ld_register_fullname', ''));
                if ($this->_hasParam('ld_register_email')) {
                    $user['email'] = $email = $this->_getParam('ld_register_email');
                }
                $user['password'] = $password = $this->_getParam('ld_register_password');
                $password_again = $this->_getParam('ld_register_password_again');

                // Basic validation
                if (empty($username)) {
                    throw new Exception( $this->translate("Username can't be empty.") );
                } else if ($tokenUser['username'] != $username
                    && $exists = $this->site->getModel('users')->getUserBy('username', $username)) {
                    throw new Exception( $this->translate("User with this username already exists.") );
                } else if (empty($email)) {
                    throw new Exception( $this->translate("Email can't be empty.") );
                } else if (empty($password) && empty($password_again)) {
                    throw new Exception( $this->translate("Password can't be empty.") );
                } else if ($password != $password_again) {
                    throw new Exception( $this->translate("Passwords must match.") );
                }

                // Empty token
                $user['token'] = '';

                // Activated
                $user['activated'] = true;

                Ld_Plugin::doAction('Auth:activate:validate', $this->_getAllParams());

                // Update user
                $this->site->updateUser($tokenUsername, $user);

                // Fix Admin roles
                $userRoles = $this->admin->getUserRoles();
                if ($tokenUsername != $username && isset($userRoles[$tokenUsername])) {
                    $userRoles[$username] = $userRoles[$tokenUsername];
                    unset($userRoles[$tokenUsername]);
                    $this->admin->setUserRoles($userRoles);
                }

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

    function lostpasswordAction()
    {
        if ($this->_hasParam('token')) {

            $token = $this->_getParam('token');
            $this->view->user = $user = $this->site->getModel('users')->getUserBy('lost_password_token', $token);
            if (empty($user) || empty($token)) {
                throw new Exception("Invalid token.");
            }
            $this->view->token = true;

            if ($this->getRequest()->isPost() && $this->_hasParam('ld_reset_password')) {

                try {

                    $reset_password = $this->_getParam('ld_reset_password');
                    $reset_password_again = $this->_getParam('ld_reset_password_again');
                    if (empty($reset_password) && empty($reset_password_again)) {
                        throw new Exception( $this->translate("Password can't be empty.") );
                    } else if ($reset_password != $reset_password_again) {
                        throw new Exception( $this->translate("Passwords must match.") );
                    }

                    $user['password'] = $reset_password;
                    $user['lost_password_token'] = "";
                    $this->site->updateUser($user['username'], $user);

                    // Authenticate with credentials, and remember
                    Ld_Auth::rememberIdentity($user['username']);
                    Ld_Auth::authenticate($user['username'], $user['password']);

                    $this->view->fertig = true;

                } catch (Exception $e) {
                    $this->view->error = $e->getMessage();
                    return;
                }
            }

        }

        if ($this->getRequest()->isPost() && $this->_hasParam('openid_identifier')) {

            try {

                $this->view->user = $user = $this->getSite()->getUser( $this->_getParam('openid_identifier') );
                if (empty($user)) {
                    throw new Exception( $this->translate("Identity not found.") );
                }

                $token = Ld_Auth::generatePhrase();
                $user['lost_password_token'] = $token;
                $this->site->updateUser($user['username'], $user);
                $this->sendResetPasswordEmail($user);
                $this->view->sent = true;

            } catch (Exception $e) {

                $this->view->error = $e->getMessage();
                return;

            }

        }
    }

    function sendResetPasswordEmail($user)
    {
        $resetUrl = $this->admin->buildUrl(array(
            'module' => 'default', 'controller' => 'auth', 'action' => 'lostPassword', 'token' => $user['lost_password_token']));

        $text =
            'Someone has asked to reset the password for the following site and username. ' . "\n" .
            "\n" .
            $this->site->getName() . " " . $this->site->getUrl() . "\n" .
            "\n" .
            "Username: " . $user['username'] . "\n" .
            "\n" .
            "To reset your password visit the following address, otherwise just ignore this email and nothing will happen." . "\n" .
            $resetUrl;

        $mail = new Zend_Mail('UTF-8');
        $mail->addTo($user['email']);
        $mail->setFrom('ladistribution@' . $this->site->getHost());
        $mail->setSubject('Password Reset on ' . $this->site->getName());
        $mail->setBodyText($text);
        $mail = Ld_Plugin::applyFilters('Auth:resetPasswordEmail', $mail);
        $mail->send();
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
