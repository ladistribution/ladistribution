<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @subpackage Ld_Controller_Action_Helper
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Controller_Action_Helper_Auth extends Ld_Controller_Action_Helper_Abstract
{

    public function authenticate()
    {
        // Callback from Plugins
        $result = Ld_Plugin::applyFilters('Ld_Controller_Action_Helper_Auth:callback', null, $this->getRequest());

        // Callback from "Connect" adapter
        if ($this->_getParam('ld_auth_action') == 'connect') {
            $auth = Zend_Auth::getInstance();
            $adapter = new Ld_Auth_Adapter_Connect();
            $result = $auth->authenticate($adapter);
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {

            return Ld_Auth::authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

        } elseif ($this->_getParam('ld_auth_action') == 'login') {

            // Parameter name in the login form
            if ($this->_hasParam('openid_identifier')) {
                $this->_setParam('ld_auth_username', $this->_getParam('openid_identifier'));
            }

            // New prefered parameter name
            if ($this->_hasParam('ld_identity')) {
                $this->_setParam('ld_auth_username', $this->_getParam('ld_identity'));
            }

            // Login with Plugins
            $result = Ld_Plugin::applyFilters('Ld_Controller_Action_Helper_Auth:login', $result, $this->getRequest());

            // Try to use "Connect" adapter if URL is submitted
            // TODO: should also be triggered if submitted identity is set to delegate login to a "Connect" enabled website
            if (empty($result) || !$result->isValid()) {
                if (Zend_Uri_Http::check($this->_getParam('ld_auth_username'))) {
                    $auth = Zend_Auth::getInstance();
                    $adapter = new Ld_Auth_Adapter_Connect();
                    $adapter->setIdentityUrl($this->_getParam('ld_auth_username'));
                    $result = $auth->authenticate($adapter);
                }
            }

            if ($this->_hasParam('ld_auth_username') && $this->_hasParam('ld_auth_password')) {
                $result = Ld_Auth::authenticate(
                    $this->_getParam('ld_auth_username'), $this->_getParam('ld_auth_password'), $this->_getParam('ld_auth_remember')
                );
            }

        }

        if ($result && $result->isValid()) {
            Ld_Auth::rememberIdentity( $result->getIdentity() );
            $this->_redirectToReferer();
        }

        return $result;
    }

    protected function _redirectToReferer()
    {
        $session = new Zend_Session_Namespace("Ld_Auth_Login");
        if (isset($session->referer)) {
            $redirect = $session->referer;
            unset($session->referer);
        } else {
            $redirect = Zend_Registry::get('site')->getUrl();
        }
        $this->_redirect($redirect);
    }

    protected function _redirect($url)
    {
        header("Location:$url");
        exit;
    }

}
