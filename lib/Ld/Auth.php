<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Auth
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Auth
{

    public static function generatePhrase($length = 64)
    {
        $chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $i = 0;
        $phrase = "";
        while ($i < $length) {
            $phrase .= $chars{mt_rand(0,strlen($chars)-1)};
            $i++;
        }
        return $phrase;
    }

    public static function logout()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $auth->clearIdentity();
        }
    }

    public static function authenticate($username, $password)
    {
        $auth = Zend_Auth::getInstance();
        $adapter = new Ld_Auth_Adapter_File();
        $adapter->setCredentials($username, $password);
        $result = $auth->authenticate($adapter);
        return $result;
    }

    public static function isAuthenticated()
    {
        return Zend_Auth::getInstance()->hasIdentity();
    }

    public static function getIdentity()
    {
        return Zend_Auth::getInstance()->getIdentity();
    }

    public static function getUsername()
    {
        if (self::isAuthenticated()) {
            $user = self::getUser();
            if ($user) {
                return $user['username'];
            }
        }
        return null;
    }

    public static function isOpenid()
    {
        $identity = self::getIdentity();
        if (substr($identity, 0, 7) == 'http://' || substr($identity, 0, 8) == 'https://') {
            return true;
        }
        return false;
    }

    public static function isAnonymous()
    {
        $user = self::getUser();
        if ($user) {
            return $user['username'] == 'anonymous' ? true : false;
        }
        return true;
    }

    public static function getUser()
    {
        if (self::isAuthenticated()) {
            $identity = self::getIdentity();
            if (self::isOpenid()) {
                $user = Zend_Registry::get('site')->getUserByUrl($identity);
                if (empty($user)) {
                    $user = array('fullname' => "Anonymous ($identity)", 'username' => 'anonymous');
                }
                return $user;
            }
            return Zend_Registry::get('site')->getUser($identity);
        }
        return null;
    }

}
