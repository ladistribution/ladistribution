<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @subpackage Ld_Controller_Action_Helper
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Ld_Controller_Action_Helper_Auth extends Zend_Controller_Action_Helper_Abstract
{

    public function authenticate()
    {
        if ($this->_getParam('ld_auth_action') == 'logout') {

            $this->logout();
            $this->_redirect( Zend_Registry::get('site')->getUrl() );

        } else if ($this->_getParam('ld_auth_action') == 'login') {

            $result = Ld_Auth::authenticate($this->_getParam('ld_auth_username'), $this->_getParam('ld_auth_password'));

            // if authentication Fail, we try to log in with OpenID
            if (!$result->isValid() && Zend_Uri_Http::check($this->_getParam('ld_auth_username'))) {
                $this->_setParam('openid_action', 'login');
                $this->_setParam('openid_identifier', $this->_getParam('ld_auth_username'));
            } else {
                return $result;
            }
        }

        if ($this->_getParam('openid_action') || $this->_getParam('openid_mode')) {
            if ($this->_getParam('action') != 'add-identity') {
                return $this->_authenticateWithOpenid();
            }
        }

        return null;
    }

    protected function _authenticateWithOpenid()
    {
        $auth = Zend_Auth::getInstance();

        $root = 'http://' . $this->getRequest()->getServer('SERVER_NAME') . $this->getRequest()->getBaseUrl();

        $adapter = new Zend_Auth_Adapter_OpenId($this->_getParam('openid_identifier'), null, null, $root);

        if ($this->_getParam('openid_action') == 'login' && $this->_getParam('openid_identifier')) {

            // we start the OpenID process
            // this should likely redirect to the OpenID provider
            return $auth->authenticate($adapter);

        } else if ($this->_getParam('openid_mode') == 'id_res') {

            $result = $auth->authenticate($adapter);

            // if user is correctly authenticated with OpenID
            // but with an identity not attached to a local account
            // we clear the identity to cancel the authentication
            // and return an authentication failure
            if ($result->isValid()) {
                $user = Ld_Auth::getUser();
                if (empty($user)) {
                    $auth->clearIdentity();
                    return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, null);
                }
            }

            return $result;

        }
    }

    // protected function _authenticateWithOauth()
    // {
    //     require_once 'OAuth/OAuthRequestVerifier.php';
    //     if (OAuthRequestVerifier::requestIsSigned()) {
    //         try {
    //             $req = new OAuthRequestVerifier();
    //             $this->username = $req->verify();
    //             return $this->username;
    //         } catch (OAuthException $e) {
    //             $message = $e->getMessage();
    //             Ld_Auth::unauthorized($message);
    //         }
    //     }
    // }

    protected function _getParam($id)
    {
        return $this->getRequest()->getParam($id);
    }

    protected function _setParam($id, $value)
    {
        return $this->getRequest()->setParam($id, $value);
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
