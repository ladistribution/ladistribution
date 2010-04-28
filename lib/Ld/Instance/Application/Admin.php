<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Instance
 * @subpackage Ld_Instance_Application
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Instance_Application_Admin extends Ld_Instance_Application_Local
{

    public function getUsers()
    {
        $userOrder = $this->getUserOrder();

        $users = array();
        foreach ($this->getUserRoles() as $username => $role) {
            $user = $this->getSite()->getUser($username);
            if (empty($user)) {
                continue;
            }
            $user['order'] = isset($userOrder[$username]) ? $userOrder[$username] : 999;
            $users[$username] = $user;
        }

        uasort($users, array('Ld_Utils', "sortByOrder"));

        return $users;
    }

    public function getRouter()
    {
        if (isset($this->router)) {
            return $this->router;
        }
        $router = new Zend_Controller_Router_Rewrite();
        $config = new Zend_Config_Ini($this->getAbsolutePath()  .'/routes.ini');
        $router->addDefaultRoutes();
        $router->addConfig($config);
        return $this->router = $router;
    }

    public function getUrl($params = array(), $name = 'default')
    {
        $baseUrl = $this->getSite()->getPath();
        if (constant('LD_REWRITE') == false || self::getSite()->getConfig('root_admin') != 1) {
            $baseUrl .=  '/' . $admin->getPath();
        }
        if (constant('LD_REWRITE') == false) {
            $baseUrl .= '/index.php';
        }

        $router = $this->getRouter();
        $route = $router->getRoute($name);
        $url = $route->assemble($params, true);

        $url = $baseUrl . '/' . $url;

        return $url;
    }

}
