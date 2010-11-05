<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @subpackage Ld_Controller_Action_Helper
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Ld_OpenId_Extension_Sreg extends Zend_OpenId_Extension_Sreg
{
    function parseResponse($params)
    {
        $result = parent::parseResponse($params);
        return true;
    }
}

class Ld_Controller_Action_Helper_Auth extends Ld_Controller_Action_Helper_Abstract
{

    public function authenticate()
    {
        $referer = $this->_getReferer();

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $result = Ld_Auth::authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        }

        if ($this->_getParam('ld_auth_action') == 'logout') {

            $this->logout();

            if (isset($referer)) {
                $this->_redirect($referer);
            } else {
                $this->_redirect( Zend_Registry::get('site')->getUrl() );
            }

        } else if ($this->_getParam('ld_auth_action') == 'login') {

            if ($this->_hasParam('openid_identifier')) {
                $openid_identifier = trim( $this->_getParam('openid_identifier') );
                $this->_setParam('ld_auth_username', $openid_identifier);
            }

            if (Zend_Uri_Http::check($this->_getParam('ld_auth_username'))) {
                return $this->_authenticateWithOpenid();
            }

            if ($this->_hasParam('ld_auth_username') && $this->_hasParam('ld_auth_password')) {
                $result = Ld_Auth::authenticate(
                    $this->_getParam('ld_auth_username'), $this->_getParam('ld_auth_password'), $this->_getParam('ld_auth_remember')
                );
                if ($result->isValid()) {
                    Ld_Auth::rememberIdentity( $result->getIdentity() );
                    if (isset($referer)) {
                        $this->_redirect($referer);
                    }
                }
                return $result;
            }

            return null;

        }

        if ($this->_getParam('openid_action') || $this->_getParam('openid_mode')) {
            if ($this->_getParam('action') != 'add-identity') {
                return $this->_authenticateWithOpenid();
            }
        }

        return null;
    }

    protected function _getOpenidAuthAdapter()
    {
        $root = Zend_Registry::get('site')->getUrl();

        $sreg = new Ld_OpenId_Extension_Sreg(
            array('nickname' => true, 'email' => true, 'fullname' => true), null, 1.1);

        $storage = Zend_Registry::get('instance')->getOpenidConsumerStorage();

        $adapter = new Zend_Auth_Adapter_OpenId($this->_getParam('openid_identifier'), $storage, null, $root, $sreg);

        $httpClient = new Zend_Http_Client();
        $httpClient->setConfig(array('maxredirects' => 4, 'timeout' => 15, 'useragent' => 'La Distribution OpenID Consumer'));
        $adapter->setHttpClient($httpClient);

        return $adapter;
    }

    protected function _authenticateWithOpenid()
    {
        $auth = Zend_Auth::getInstance();

        $adapter = $this->_getOpenidAuthAdapter();

        if ($this->_getParam('openid_action') == 'login' && $this->_getParam('openid_identifier')) {

            // we register the referer in the session
            $this->_session = new Zend_Session_Namespace("ld_openid");
            $this->_session->referer = $this->_getReferer();

            // we start the OpenID process
            // this should likely redirect to the OpenID provider
            $result = $auth->authenticate($adapter);

            return $result;

        } else if ($this->_getParam('openid_mode') == 'id_res') {

            $result = $auth->authenticate($adapter);

            // if user is correctly authenticated with OpenID
            if ($result->isValid()) {
                Ld_Auth::rememberIdentity( $result->getIdentity() );
            } else {
                Ld_Auth::logout();
            }

            return $result;

        }
    }

    protected function _getReferer()
    {
        if ($this->_hasParam('ld_referer')) {
            return $this->_getParam('ld_referer');
        } else {
            return $this->getRequest()->getServer('HTTP_REFERER');
        }
    }

    protected function _redirect($url)
    {
        header("Location:$url");
        exit;
    }

    public function logout() { Ld_Auth::logout(); }

    public function getUser() { return Ld_Auth::getUser(); }

    public function isAuthenticated() { return Ld_Auth::isAuthenticated(); }

}
