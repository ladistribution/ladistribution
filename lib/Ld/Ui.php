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

        $router = new Zend_Controller_Router_Rewrite();
        $config = new Zend_Config_Ini($admin->getAbsolutePath()  .'/routes.ini');
        $router->addDefaultRoutes();
        $router->addConfig($config);

        $baseUrl = self::getSite()->getPath() . '/' . $admin->getPath();
        if (constant('LD_REWRITE') == false) {
            $baseUrl .= '/index.php';
        }

        $route = $router->getRoute($name);
        $url = $route->assemble($params, true);

        return $baseUrl . '/' . $url;
    }

    public static function superBar($params = array())
    {
        self::super_bar($params);
    }

    public static function super_bar($params = array())
    {
        $site = self::getSite();
        $admin = self::getAdmin();

        $applications = $site->getApplicationsInstances();

        $isAdmin = false;
        if ($admin->getUserRole() == 'admin') {
            $isAdmin = true;
        } else {
            $users = $site->getUsers();
            if (empty($users)) {
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

        echo $view->render('super-bar.phtml');
    }
    
    public static function top_bar()
    {
        $admin = self::getAdmin();

        $view = self::getView();

        $view->site = self::getSite();

        echo $view->render('top-bar.phtml');
    }

}
