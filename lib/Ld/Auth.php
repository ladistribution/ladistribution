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
        $chars = "1234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
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

    public static function authenticate($username, $password, $remember = false)
    {
        $auth = Zend_Auth::getInstance();
        if ($remember) {
            $storage = $auth->getStorage();
            $storage->setOptions(array('cookieExpire' => time()+60*60*24*30));
        }
        $adapter = new Ld_Auth_Adapter_File();
        $adapter->setCredentials($username, $password);
        $result = $auth->authenticate($adapter);
        return $result;
    }

    public static function isAuthenticated()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $identity = Zend_Auth::getInstance()->getIdentity();
            if (!empty($identity)) {
                return true;
            }
        }
        return false;
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

    public static function isAdmin()
    {
        $site = Zend_Registry::get('site');
        $role = $site->getAdmin()->getUserRole();
        return $role == 'admin';
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

    public static function getIdentities()
    {
        $site = Zend_Registry::get('site');
        $identities = array();
        if (isset($_COOKIE['ld-identities'])) {
            $cookieIdentities = explode(";", $_COOKIE['ld-identities']);
            $usernames = array();
            foreach ($cookieIdentities as $identity) {
                $user = $site->getUser($identity);
                if (empty($user) || in_array($user['username'], $usernames)) {
                    continue;
                } else {
                    $identities[$identity] = $user;
                    $usernames[] = $user['username'];
                }
            }
        }
        return $identities;
    }

    public static function rememberIdentity($identity)
    {
        $site = Zend_Registry::get('site');
        if (isset($_COOKIE['ld-identities'])) {
            $identities = explode(";", $_COOKIE['ld-identities']);
            array_unshift($identities, $identity);
            $identities = array_unique($identities);
        } else {
            $identities = array();
            $identities[] = $identity;
        }
        $path = $site->getPath();
        $cookiePath = empty($path) ? '/' : $path;
        $_COOKIE['ld-identities'] = implode(";", $identities);
        setCookie('ld-identities', implode(";", $identities), time() + 365 * 24 * 60 * 60, $cookiePath);
    }

    public static function forgetIdentity($identity)
    {
        $site = Zend_Registry::get('site');
        if (isset($_COOKIE['ld-identities'])) {
            $identities = explode(";", $_COOKIE['ld-identities']);
            $key = array_search($identity, $identities);
            if ($key !== false) {
                unset($identities[$key]);
                $path = $site->getPath();
                $cookiePath = empty($path) ? '/' : $path;
                $_COOKIE['ld-identities'] = implode(";", $identities);
                setCookie('ld-identities', implode(";", $identities), time() + 365 * 24 * 60 * 60, $cookiePath);
            }
        }
    }

}
