<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Ui
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Ui
{

    protected static $_site = null;

    protected static $_admin = null;

    protected static $_isAdmin = null;

    protected static $_applications = array();

    public function getSite()
    {
        return isset(self::$_site) ? self::$_site : self::$_site = Zend_Registry::get('site');
    }

    public function getAdmin()
    {
        return isset(self::$_admin) ? self::$_admin : self::$_admin = self::getSite()->getAdmin();
    }

    public static function getView()
    {
        $view = new Zend_View();
        $view->addScriptPath(dirname(__FILE__) . '/Ui/scripts');
        return $view;
    }

    public static function isAdmin()
    {
        if (isset(self::$_isAdmin)) {
            return self::$_isAdmin;
        }

        $admin = self::getAdmin();

        if ($admin->getUserRole() == 'admin') {
            return self::$_isAdmin = true;
        }

        $roles = $admin->getUserRoles();
        if (empty($roles) && !$site->isChild()) {
            return self::$_isAdmin = true;
        }

        return self::$_isAdmin = false;
    }

    public static function getApplications()
    {
        $site = self::getSite();
        if (empty(self::$_applications)) {
            foreach ($site->getInstances('application') as $id => $infos) {
                $instance = $site->getInstance($id);
                if (isset($instance)) {
                    self::$_applications[$id] = $instance;
                }
            }
        }
        return self::$_applications;
    }

    public static function getAdminUrl($params = array(), $name = 'default')
    {
        return self::getAdmin()->buildUrl($params, $name);
    }

    public static function getIdentityUrl($user)
    {
        return self::getAdmin()->getIdentityUrl($user);
    }

    public static function getApplicationSettingsUrl($application = null, $action = null)
    {
        if (empty($application)) {
            $application = Zend_Registry::get('application');
        }
        if ($id = $application->getId()) {
            $params = array('controller' => 'instance', 'id' => $id, 'action' => $action);
            return self::getAdminUrl($params, 'instance-action');
        }
    }

    /* Deprecated */
    public static function superBar($options = array())
    {
    }

    /* Deprecated */
    public static function super_bar($options = array())
    {
    }

    public static function topBar($options = array())
    {
        echo self::getTopBar($options);
    }

    public static function top_bar($options = array())
    {
        self::topBar($options);
    }

    public static function getTopBar($options = array())
    {
        $view = self::getView();

        $view->site = $site = self::getSite();
        $view->admin = $admin = self::getAdmin();

        if (isset($options['loginUrl'])) {
            $view->loginUrl = $options['loginUrl'];
        } else {
            $view->loginUrl = $admin->getAuthUrl(array('referer' => true), 'login');
        }

        if (isset($options['logoutUrl'])) {
            $view->logoutUrl = $options['logoutUrl'];
        } else {
            $view->logoutUrl = $admin->getAuthUrl(array('referer' => true), 'logout');
        }

        $view->registerUrl = self::getAdminUrl(array(
              'module' => 'default', 'controller' => 'auth', 'action' => 'register'
        ));

        $view->completeUrl = self::getAdminUrl(array(
              'module' => 'default', 'controller' => 'auth', 'action' => 'complete'
        ));

        if ($user = Ld_Auth::getUser()) {
            $view->userUrl = $admin->getIdentityUrl($user);
        }

        $out  = $view->render('top-bar.phtml');

        $out .= $view->render('top-menu.phtml');

        return $out;
    }

    public static function topNav($options = array())
    {
        echo self::getTopNav($options);
    }

    public static function getTopNav($options = array())
    {
        $view = self::getView();
        if (isset($options['application'])) {
            $view->application = $options['application'];
        } elseif (Zend_Registry::isRegistered('application')) {
            $view->application = Zend_Registry::get('application');
        } else {
            return;
        }
        return $view->render('top-nav.phtml');
    }

    public static function getTopMenu($options = array())
    {
        $view = self::getView();
        $view->site = self::getSite();
        $out = $view->render('top-menu.phtml');
        return $out;
    }

    public static function getCssUrl($file, $package)
    {
        $infos = self::getPackageInfos($package, 'css');
        $url = self::getSite()->getUrl('css') . $file . '?v=' . $infos['version'];
        return $url;
    }

    public static function getJsUrl($file, $package)
    {
        $infos = self::getPackageInfos($package, 'js');
        $url = self::getSite()->getUrl('js') . $file . '?v=' . $infos['version'];
        return $url;
    }

    public static function getPackageInfos($package, $type)
    {
        $infos = self::getSite()->getLibraryInfos($package);
        if (empty($infos)) {
            $infos = self::getSite()->getLibraryInfos("$type-$package");
        }
        if ($infos['type'] == 'application') {
            $instances = self::getSite()->getInstances($package, 'package');
            if (is_array($instances) && isset($instances[0])) {
                $infos = $instances[0]->getInfos();
            }
        }
        return $infos;
    }

    public static function getDefaultAvatarUrl($size = 48)
    {
        $imageSize = in_array($size, array(16, 32, 48)) ? $size : 48;
        $url = self::getSite()->getUrl('css') . "/ld-ui/img/avatar-$imageSize.png";
        return $url;
    }

    public static function getAvatarUrl($user = null, $size = 48)
    {
        $url = self::getDefaultAvatarUrl($size);
        $url = Ld_Plugin::applyFilters('Ui:getAvatarUrl', $url, $user, $size);
        return $url;
    }

    public static function getAvatar($user = null, $size = 32)
    {
        $url = self::getAvatarUrl($user, $size);

        $html = sprintf('<img src="%1$s" width="%2$s" height="%2$s" alt="" class="avatar"/>', $url, $size);
        $html = Ld_Plugin::applyFilters('Ui:getAvatarHtml', $html, $user, $size);

        return $html;
    }

    public static function getDefaultSiteColors()
    {
        return array(
            "ld-colors-background"   => "ffffff", "ld-colors-border"   => "cccccc", "ld-colors-title"   => "009dff", "ld-colors-text"   => "4a4a4a",
            "ld-colors-background-2" => "1f1f1f", "ld-colors-border-2" => "3d3d3d", "ld-colors-title-2" => "f1f1f1", "ld-colors-text-2" => "f1f1f1",
            "ld-colors-background-3" => "fcfcfc", "ld-colors-border-3" => "cccccc", "ld-colors-title-3" => "4a4a4a", "ld-colors-text-3" => "4a4a4a",
        );
    }

    public static function computeColors($colors, $stored = null)
    {
        foreach (array('base', 'bars', 'panels') as $scheme) {
            $default = empty($stored) ? 1 : 0;
            $colors["$scheme-default"] = isset($stored["$scheme-default"]) ? $stored["$scheme-default"] : $default;
        }

        if (empty($stored)) {
            return $colors;
        }

        $parts = array(
            'base' => array(
                'ld-colors-background' => $stored['ld-colors-background'], "ld-colors-border" => $stored['ld-colors-border'],
                'ld-colors-title' => $stored['ld-colors-title'], 'ld-colors-text' => $stored['ld-colors-text']
            ),
            'bars' => array(
                'ld-colors-background-2' => $stored['ld-colors-background-2'], 'ld-colors-border-2' => $stored['ld-colors-border-2'],
                'ld-colors-title-2' => $stored['ld-colors-title-2'], 'ld-colors-text-2' => $stored['ld-colors-text-2']
            ),
            'panels' => array(
                'ld-colors-background-3' => $stored['ld-colors-background-3'], 'ld-colors-border-3' => $stored['ld-colors-border-3'],
                'ld-colors-title-3' => $stored['ld-colors-title-3'], 'ld-colors-text-3' => $stored['ld-colors-text-3']
            )
        );

        foreach ($parts as $id => $partColors) {
            if (empty($colors["$id-default"]) || $colors["$id-default"] === 0) {
                foreach ($partColors as $id => $color) if ($color) $colors[$id] = $color;
            }
        }

        $colors['version'] = isset($stored['version']) ? $stored['version'] : null;

        return $colors;
    }

    public static function getSiteStyleUrl($parts = null)
    {
        $version = self::getSite()->getConfig('appearance_version');
        $url = self::getAdminUrl(array(
            'module' => 'slotter', 'controller' => 'appearance', 'action' => 'style',
            'parts' => $parts
        ));
        return $url . '?v=' . $version;
    }

    public static function getApplicationStyleUrl($parts = null, $application = null)
    {
        if (empty($application)) {
            $application = Zend_Registry::get('application');
        }
        $colors = $application->getColors();
        $appearance_version = self::getSite()->getConfig('appearance_version');
        $version = substr(md5($appearance_version . serialize($colors)), 0, 10);
        $url = self::getAdminUrl(array(
            'module' => 'slotter', 'controller' => 'appearance', 'action' => 'style',
            'id' => $application->getId(), 'parts' => $parts
        ));
        return $url . '?v=' . $version;
    }

    public static function getApplicationColors($application = null)
    {
        if (empty($application)) {
            $application = Zend_Registry::get('application');
        }
        return $application->getColors();
    }

    public static function relativeTime($time, $now = false)
    {
        $time = (int) $time;
        $curr = $now ? $now : time();
        $shift = $curr - $time;

        if ($shift < 45) {
            $diff = $shift;
            $term = "second";
        } elseif ($shift < 2700) {
            $diff = round($shift / 60);
            $term = "minute";
        } elseif ($shift < 64800) {
            $diff = round($shift / 60 / 60);
            $term = "hour";
        } elseif ($shift < 64800 * 28) {
            $diff = round($shift / 60 / 60 / 24);
            $term = "day";
        } elseif ($shift < 64800 * 28 * 11) {
            $diff = round($shift / 60 / 60 / 24 / 30);
            $term = "month";
        } else {
            $diff = round($shift / 60 / 60 / 24 / 30 / 12);
            $term = "year";
        }

        if ($diff > 1) {
            $term .= "s";
        }

        return "$diff $term ago";
    }

    public static function contrastColor($color, $diff = 20)
    {
        $rgb = '';
        for ($x=0; $x<3; $x++) {
            $c = hexdec(substr($color, (2*$x), 2)) - $diff;
            $c = ($c < 0) ? 0 : ( ($c > 255) ? 'ff' : dechex($c) );
            $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
        }
        return $rgb;
    }

    /* Deprecated */

    public static function getSiteColors()
    {
        return self::getSite()->getColors();
    }

}
