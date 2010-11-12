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

class Ld_Instance_Application_Admin extends Ld_Instance_Application
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

    public function getUserRole($username = null)
    {
        // There is no user roles defined, we make the user admin.
        $roles = $this->getUserRoles();
        if (!$this->getSite()->isChild() && empty($roles)) {
            return 'admin';
        }
        return parent::getUserRole($username);
    }

    public function getAdministrators()
    {
        $users = array();
        foreach ($this->getUserRoles() as $username => $role) {
            if ($role == 'admin') {
                $user = $this->getSite()->getUser($username);
                if (empty($user)) {
                    continue;
                }
                $users[$username] = $user;
            }
        }
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

    public function buildUrl($params = array(), $name = 'default', $reset = true)
    {
        $site = $this->getSite();

        // $baseUrl = $this->getSite()->getPath();
        $baseUrl = 'http://' . $site->getHost($this->domain) . $site->getPath();

        // if (constant('LD_REWRITE') == false || $site->getConfig('root_admin') != 1) {
        //     $baseUrl .=  '/' . $this->getPath();
        // }
        $baseUrl .=  '/' . $this->getPath();

        if (constant('LD_REWRITE') == false) {
            $baseUrl .= '/index.php';
        }

        $router = $this->getRouter();
        $route = $router->getRoute($name);
        $url = $route->assemble($params, $reset);

        $url = empty($url) ? 'slotter' : $url;

        $url = $baseUrl . '/' . $url;

        return $url;
    }

    public function getUrl()
    {
        return $this->buildUrl();
    }

    public function getOpenidDir()
    {
        $openidDirectory = $this->getAbsolutePath() . '/openid';
        Ld_Files::createDirIfNotExists($openidDirectory);
        return $openidDirectory;
    }

    public function getOpenidAuthUrl()
    {
        return $this->buildUrl(array('module' => 'identity', 'controller' => 'openid', 'action' => 'auth'), 'default', false);
    }

    public function getIdentityUrl($username)
    {
        return Zend_OpenId::absoluteURL($this->buildUrl(array('module' => 'identity', 'controller' => 'openid', 'id' => $username), 'identity'));
    }

    public function getOpenidProvider($username = null, $login = false)
    {
        if (empty($this->_openidProvider)) {
            $storage = new Zend_OpenId_Provider_Storage_File($this->getOpenidDir());
            $this->_openidProvider = new Zend_OpenId_Provider($this->getOpenidAuthUrl(), null, null, $storage);
        }
        if (isset($username)) {
            // register, and log in ...
            $identityUrl = $this->getIdentityUrl($username);
            if (!$this->_openidProvider->hasUser($identityUrl)) {
                $this->_openidProvider->register($identityUrl, $username);
            }
            if ($login) {
                $this->_openidProvider->login($identityUrl, $username);
            }
        }
        return $this->_openidProvider;
    }

    public function getOpenidConsumerStorage()
    {
        if (empty($this->_openidConsumerStorage)) {
            $this->_openidConsumerStorage = new Zend_OpenId_Consumer_Storage_File( $this->getOpenidDir() );
        }
        return $this->_openidConsumerStorage;
    }

    public function getOpenidConsumer()
    {
        if (empty($this->_openidConsumer)) {
            $storage = $this->getOpenidConsumerStorage();
            $this->_openidConsumer = new Zend_OpenId_Consumer($storage);
        }
        return $this->_openidConsumer;
    }

}
