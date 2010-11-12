<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Dispatch
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

/**
 * @category   Ld
 * @package    Ld_Dispatch
 */
class Ld_Dispatch
{

    public static function dispatch()
    {
        $site = Zend_Registry::get('site');

        $root_application = self::_getRootApplicationPath();

        // Get Instance
        $instance = $site->getInstance($root_application);
        if (empty($instance)) {
            $root_application = self::getDefaultRootApplicationPath();
            $instance = $site->getInstance($root_application);
        }

        switch ($instance->getPackageId()) {
            case 'dokuwiki':
                $script = 'doku.php';
                break;
            default:
                $script = 'index.php';
        }

        return array($root_application, $script);
    }

    protected static function _getRootApplicationPath()
    {
        $site = Zend_Registry::get('site');

        if (self::isAdmin()) {
            return self::getAdminPath();
        }

        // Get Root Application
        if (defined('LD_MULTI_DOMAINS') && constant('LD_MULTI_DOMAINS')) {
            foreach ($site->getDomains() as $domain) {
                if ($domain['host'] == $_SERVER['SERVER_NAME'] && !empty($domain['default_application'])) {
                    return $domain['default_application'];
                }
            }
        }

        // Should not be used anymore with new domain code
        $root_application = $site->getConfig('root_application');
        if (!empty($root_application)) {
            return $root_application;
        }

        return self::_getDefaultRootApplicationPath();
    }

    protected static function getAdminPath()
    {
        $admin = Zend_Registry::get('site')->getAdmin();
        return $admin->getPath();
    }

    protected static function _getDefaultRootApplicationPath()
    {
        return self::getAdminPath();
    }

    protected static function getPart()
    {
        $site = Zend_Registry::get('site');
        $path = self::str_replace_once($site->getPath() . '/', '', $_SERVER["REQUEST_URI"]);
        $parts = explode('/', $path);
        if (!empty($parts)) {
            return $parts[0];
        }
        return null;
    }

    protected static function isAdmin()
    {
        if ($part = self::getPart()) {
            $site = Zend_Registry::get('site');
            if ($site->getConfig('root_admin') == 1) {
                $modules = Ld_Files::getDirectories($site->getDirectory('shared') . '/modules');
                $modules[] = 'auth';
                if (in_array($part, $modules)) {
                    return true;
                }
            }
            if ($part == 'admin') {
                return true;
            }
            if ($user = $site->getUser($part)) {
                define('LD_USER_CONTEXT', $user['username']);
                return true;
            }
        }
        return false;
    }

    protected static function str_replace_once($needle, $replace, $haystack)
    {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

}
