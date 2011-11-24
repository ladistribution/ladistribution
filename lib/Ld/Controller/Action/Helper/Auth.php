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

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Ld_Controller_Action_Helper_Auth extends Ld_Controller_Action_Helper_Abstract
{

    public function authenticate()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {

            return Ld_Auth::authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

        } else if ($this->_getParam('ld_auth_action') == 'login') {

            if ($this->_hasParam('openid_identifier')) {
                $openid_identifier = trim( $this->_getParam('openid_identifier') );
                $this->_setParam('ld_auth_username', $openid_identifier);
            }

            if ($this->_hasParam('ld_auth_username') && $this->_hasParam('ld_auth_password')) {

                $result = Ld_Auth::authenticate(
                    $this->_getParam('ld_auth_username'), $this->_getParam('ld_auth_password'), $this->_getParam('ld_auth_remember')
                );

                if ($result->isValid()) {
                    Ld_Auth::rememberIdentity( $this->_getParam('ld_auth_username') );
                    $this->_redirectToReferer();
                }

                return $result;
            }

        }
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
