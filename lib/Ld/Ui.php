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

    public static $_admin = null;

    public static $_applications = array();

    public static function getSite()
    {
        return Zend_Registry::get('site');
    }

    public static function getView()
    {
        $view = new Zend_View();
        $view->addScriptPath(dirname(__FILE__) . '/Ui/scripts');
        return $view;
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
        $admin = self::getSite()->getAdmin();
        if (empty($admin)) {
            return null;
        }
        return $admin->buildUrl($params, $name);
    }

    public static function superBar($options = array())
    {
         echo self::getSuperBar($options);
    }

    public static function super_bar($options = array())
    {
        self::superBar($options);
    }

    public static function getSuperBar($params = array())
    {
        $site = self::getSite();
        $admin = self::getSite()->getAdmin();

        $applications = $site->getApplicationsInstances(array('admin'));

        $isAdmin = false;
        if ($admin && $admin->getUserRole() == 'admin') {
            $isAdmin = true;
        } else {
            $roles = $admin->getUserRoles();
            if (!$site->isChild() && empty($roles)) {
                $isAdmin = true;
            }
        }

        if ($isAdmin) {
            $applications = array_merge( array('admin' => $admin), $applications);
        }

        $view = self::getView();

        $view->site = $site;
        $view->params = $params;
        $view->isAdmin = $isAdmin;
        $view->applications = $applications;

        return $view->render('super-bar.phtml');
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
        $admin = self::getSite()->getAdmin();

        $view = self::getView();

        $view->site = self::getSite();

        if (isset($options['loginUrl'])) {
            $view->loginUrl = $options['loginUrl'];
        } else {
            $view->loginUrl = self::getAdminUrl(array(
                  'module' => 'default', 'controller' => 'auth', 'action' => 'login'
            ));
        }

        if (isset($options['logoutUrl'])) {
            $view->logoutUrl = $options['logoutUrl'];
        } else {
            $view->logoutUrl = self::getAdminUrl(array(
                  'module' => 'default', 'controller' => 'auth', 'action' => 'logout'
            ));
        }

        $view->registerUrl = self::getAdminUrl(array(
              'module' => 'default', 'controller' => 'auth', 'action' => 'register'
        ));

        $view->completeUrl = self::getAdminUrl(array(
              'module' => 'default', 'controller' => 'auth', 'action' => 'complete'
        ));

        if ($user = Ld_Auth::getUser()) {
            $view->userUrl = self::getAdminUrl(array(
                'module' => 'slotter', 'controller' => 'users', 'action' => 'edit', 'id' => urlencode($user['username'])
            ));
        }

        $view->className = isset($options['full-width']) ? 'full-width' : 'default-width';

        return $view->render('top-bar.phtml');
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

    protected static function getPackageInfos($package, $type)
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
            "ld-colors-background"   => "ffffff", "ld-colors-border"   => "cccccc", "ld-colors-title"   => "f54e00", "ld-colors-text"   => "4a4a4a",
            "ld-colors-background-2" => "ededed", "ld-colors-border-2" => "cccccc", "ld-colors-title-2" => "4a4a4a", "ld-colors-text-2" => "4a4a4a",
            "ld-colors-background-3" => "ededed", "ld-colors-border-3" => "cccccc", "ld-colors-title-3" => "4a4a4a", "ld-colors-text-3" => "4a4a4a",
        );
    }

    public static function getSiteColors()
    {
        $default = self::getDefaultSiteColors();
        $stored = Ld_Files::getJson(self::getSite()->getDirectory('dist') . '/colors.json');
        $colors = array_merge($default, $stored);
        if (empty($colors['version'])) {
            $colors['version'] = '1';
        }
        return $colors;
    }

    public static function getApplicationColors($application = null)
    {
        if (empty($application) && Zend_Registry::isRegistered('application')) {
            $application = Zend_Registry::get('application');
        }

        $colors = self::getSiteColors();

        $stored = Ld_Files::getJson($application->getAbsolutePath() . '/dist/colors.json');

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
            $colors["$id-default"] = isset($stored["$id-default"]) ? $stored["$id-default"] : 1;
            if (empty($stored["$id-default"]) || $stored["$id-default"] === 0) {
                foreach ($partColors as $id => $color) if ($color) $colors[$id] = $color;
            }
        }

        if (empty($colors['version'])) {
            $colors['version'] = '1';
        }

        return $colors;
    }

    public static function getSiteStyle($parts = null)
    {
        $view = self::getView();
        $view->part = $parts;
        $view->colors = self::getSiteColors();
        return $view->render('site-style.phtml');
    }

    public static function siteStyle()
    {
        echo self::getSiteStyle();
    }

    public static function getSiteStyleUrl($parts = null)
    {
        $colors = self::getSiteColors();
        $siteStyleUrl = self::getAdminUrl(array(
            'module' => 'slotter', 'controller' => 'appearance', 'action' => 'style', 'parts' => $parts, 'v' => $colors['version']), 'default', false);
        return $siteStyleUrl;
    }

    public static function getApplicationStyleUrl($parts = null, $application = null)
    {
        if (empty($application) && Zend_Registry::isRegistered('application')) {
            $application = Zend_Registry::get('application');
        }
        $colors = self::getApplicationColors($application);
        $applicationStyleUrl = self::getAdminUrl(array(
            'module' => 'slotter', 'controller' => 'appearance', 'action' => 'style', 'id' => $application->getId(), 'parts' => $parts, 'v' => $colors['version']), 'default', false);
        return $applicationStyleUrl;
    }

}
