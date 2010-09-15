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

class Ld_Dispatch
{

    public function dispatch()
    {
        $site = self::getSite();

        $root_application = self::getRootApplicationPath();

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

    public function getRootApplicationPath()
    {
        $site = self::getSite();

        if (self::isAdmin()) {
            return self::getAdminPath();
        }

        // Get Root Application
        foreach ($site->getDomains() as $domain) {
            if ($domain['host'] == $_SERVER['SERVER_NAME'] && !empty($domain['default_application'])) {
                return $domain['default_application'];
            }
        }

        $root_application = $site->getConfig('root_application');
        if (!empty($root_application)) {
            return $root_application;
        }

        return self::getDefaultRootApplicationPath();
    }

    public function getAdminPath()
    {
        $admin = self::getSite()->getAdmin();
        return $admin->getPath();
    }

    public function getDefaultRootApplicationPath()
    {
        return self::getAdminPath();
    }

    public function isAdmin()
    {
        $site = self::getSite();
        $path = self::str_replace_once($site->getPath() . '/', '', $_SERVER["REQUEST_URI"]);
        $parts = explode('/', $path);
        if (!empty($parts)) {
            if ($site->getConfig('root_admin') == 1) {
                $modules = Ld_Files::getDirectories($site->getDirectory('shared') . '/modules');
                $modules[] = 'auth';
                if (in_array($parts[0], $modules)) {
                    return true;
                }
            }
            else {
                if ($parts[0] == 'admin') {
                    return true;
                }
            }
        }
        return false;
    }

    public function getSite()
    {
        return Zend_Registry::get('site');
    }

    static function str_replace_once($needle, $replace, $haystack)
    {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

}
