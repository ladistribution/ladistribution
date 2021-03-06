<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Auth
 * @subpackage Ld_Auth_Adapter
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Auth_Adapter_Password implements Zend_Auth_Adapter_Interface
{

    protected $_username = null;

    protected $_password = null;

    public function setCredentials($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;
    }

    public function authenticate()
    {
        $hasher = new Ld_Auth_Hasher(8, TRUE);
        $user = Zend_Registry::get('site')->getUser($this->_username);
        if (empty($user)) {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, null);
        }
        if ($hasher->CheckPassword($this->_password, $user['hash'])) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_username);
        }
        if (md5($this->_password) == $user['hash']) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_username);
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }

}
