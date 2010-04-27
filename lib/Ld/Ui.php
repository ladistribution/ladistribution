<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Ui
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
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

    public static function getAdmin()
    {
        if (isset(self::$_admin)) {
            return self::$_admin;
        }
        $instances = self::getApplications();
        foreach ($instances as $instance) {
            if ($instance->getPackageId() == 'admin') {
                 return self::$_admin = $instance;
            }
        }
        return null;
    }

    public static function getAdminUrl($params = array(), $name = 'default')
    {
        $admin = self::getAdmin();

        if (empty($admin)) {
            return null;
        }

        $router = new Zend_Controller_Router_Rewrite();
        $config = new Zend_Config_Ini($admin->getAbsolutePath()  .'/routes.ini');
        $router->addDefaultRoutes();
        $router->addConfig($config);

        $baseUrl = self::getSite()->getPath();
        if (constant('LD_REWRITE') == false || self::getSite()->getConfig('root_admin') != 1) {
            $baseUrl .=  '/' . $admin->getPath();
        }

        if (constant('LD_REWRITE') == false) {
            $baseUrl .= '/index.php';
        }

        $route = $router->getRoute($name);
        $url = $route->assemble($params, true);

        $adminUrl = $baseUrl . '/' . $url;

        return $adminUrl;
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
        $admin = self::getAdmin();

        $applications = $site->getApplicationsInstances(array('admin'));

        $isAdmin = false;
        if ($admin && $admin->getUserRole() == 'admin') {
            $isAdmin = true;
        } else {
            $roles = $admin->getUserRoles();
            if (empty($roles)) {
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
        $admin = self::getAdmin();

        $view = self::getView();

        $view->site = self::getSite();

        if (isset($options['loginUrl'])) {
            $view->loginUrl = $options['loginUrl'];
        } else {
            $view->loginUrl = Ld_Ui::getAdminUrl(array(
                  'module' => 'default', 'controller' => 'auth', 'action' => 'login'
            ));
        }

        if (isset($options['logoutUrl'])) {
            $view->logoutUrl = $options['logoutUrl'];
        } else {
            $view->logoutUrl = Ld_Ui::getAdminUrl(array(
                  'module' => 'default', 'controller' => 'auth', 'action' => 'logout'
            ));
        }

        $view->registerUrl = Ld_Ui::getAdminUrl(array(
              'module' => 'default', 'controller' => 'auth', 'action' => 'register'
        ));

        $view->completeUrl = Ld_Ui::getAdminUrl(array(
              'module' => 'default', 'controller' => 'auth', 'action' => 'complete'
        ));

        if ($user = Ld_Auth::getUser()) {
            $view->userUrl = Ld_Ui::getAdminUrl(array(
                'module' => 'slotter', 'controller' => 'users', 'action' => 'edit', 'id' => urlencode($user['username'])
            ));
        }

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
            $infos = self::getSite()->getInstance($infos['path'])->getInfos();
        }
        return $infos;
    }

    public static function getAvatar($user = null, $size = 32)
    {
        $email = isset($user) && isset($user['email']) ? $user['email'] : '';

        if ( !empty($email) ) {
            $hash = md5( strtolower( $email ) );
            $host = sprintf( "http://%d.gravatar.com", ( hexdec( $hash{0} ) % 2 ) );
        } else {
            $host = 'http://0.gravatar.com';
        }

        $default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // wordpress default

        if ( !empty($email) ) {
            $out = "$host/avatar/{$hash}?s={$size}";
            $out .= '&amp;d=' . urlencode( $default );
        } else {
            $out = $default;
        }

        return sprintf('<img src="%1$s" width="%2$s" height="%2$s" alt="" class="avatar"/>', $out, $size);
    }

}
