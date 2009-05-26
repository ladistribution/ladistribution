<?php

require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Ld_Controller_Action_Helper_Auth extends Zend_Controller_Action_Helper_Abstract
{

    public function init()
    {
        $baseUrl = $this->getRequest()->getBaseUrl();

        if (Zend_Registry::isRegistered('authStorage')) {
            $authStorage = Zend_Registry::get('authStorage');
        } else {
            $authStorage = new Zend_Auth_Storage_Session( /* namespace */ null );
        }

        $this->_auth = Zend_Auth::getInstance();
        $this->_auth->setStorage($authStorage);

        $this->handle();
    }

    public function handle()
    {
        if ($this->_getParam('ld_auth_action') == 'logout') {
            $this->logout();
        } else if ($this->_getParam('ld_auth_action') == 'login') {
            $this->_authenticateWithUsernameAndPassword();
        }

        if ($this->_getParam('openid_action') || $this->_getParam('openid_mode')) {
            $this->_authenticateWithOpenid();
        }
    }

    public function logout()
    {
        $this->_auth->clearIdentity();
    }

    public function authenticate()
    {
        if ($this->_auth->hasIdentity()) {
            if ($user = $this->getUser()) {
                return true;
            }
            $identity = $this->_auth->getIdentity();
            $this->_auth->clearIdentity();
            throw new Exception( sprintf("Unknown user: %s.", $identity) );
        }
        return false;
    }

    public function getUser()
    {
        if ($this->_auth->hasIdentity()) {
            $identity = $this->_auth->getIdentity();
            if (substr($identity, 0, 7 == 'http://') || substr($identity, 0, 7 == 'https://')) {
                return $this->_getUserByOpenid($identity);
            }
            return $this->_getUserByUsername($identity);
        }
        return null;
    }

    protected function _authenticateWithOpenid()
    {
        $root = 'http://' . $this->getRequest()->getServer('SERVER_NAME') . $this->getRequest()->getBaseUrl();

        $adapter = new Zend_Auth_Adapter_OpenId( $this->_getParam('openid_identifier'), null, null, $root);

        if ($this->_getParam('openid_action') == 'login' && $this->_getParam('openid_identifier')) {
            return $this->_auth->authenticate($adapter);

        } else if ($this->_getParam('openid_mode') == 'id_res') {
            return $this->_auth->authenticate($adapter);
        }
    }

    protected function _authenticateWithUsernameAndPassword()
    {
        $adapter = new Ld_Auth_Adapter_File();
        $adapter->setCredentials($this->_getParam('ld_auth_username'), $this->_getParam('ld_auth_password'));
        return $this->_auth->authenticate($adapter);
    }

    protected function _authenticateWithOauth()
    {
        require_once 'OAuth/OAuthRequestVerifier.php';
        if (OAuthRequestVerifier::requestIsSigned()) {
            try {
                $req = new OAuthRequestVerifier();
                $this->username = $req->verify();
                return $this->username;
            } catch (OAuthException $e) {
                $message = $e->getMessage();
                Ld_Auth::unauthorized($message);
            }
        }
    }

    protected function _getUserByOpenid($openid)
    {
        $users = Zend_Registry::get('site')->getUsers();
        foreach ($users as $id => $user) {
            foreach ($user['identities'] as $identity) {
                if ($identity == $openid) {
                    return $user;
                }
            }
        }
        return null;
    }

    protected function _getUserByUsername($username)
    {
        $users = Zend_Registry::get('site')->getUsers();
        foreach ($users as $id => $user) {
            if ($user['username'] == $username) {
                return $user;
            }
        }
        return null;
    }

    protected function _getParam($id)
    {
        return $this->getRequest()->getParam($id);
    }

}
