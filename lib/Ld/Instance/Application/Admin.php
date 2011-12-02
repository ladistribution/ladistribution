<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Instance
 * @subpackage Ld_Instance_Application
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
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

        if (defined('LD_AJAX_USERS') && constant('LD_AJAX_USERS')) {
            uasort($users, array('Ld_Utils', "sortByOrder"));
        } else {
            ksort($users);
        }

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

        $baseUrl = $site->getPath();

        $noRewrite = defined('LD_REWRITE') && constant('LD_REWRITE') == false;
        if ($noRewrite || $site->getConfig('root_admin') != 1) {
            $baseUrl .=  '/' . $this->getPath();
        }
        if ($noRewrite) {
            $baseUrl .= '/index.php';
        }

        $router = $this->getRouter();
        $route = $router->getRoute($name);
        $url = $route->assemble($params, $reset);

        $url = empty($url) ? 'slotter' : $url;

        $url = $baseUrl . '/' . $url;

        return $url;
    }

    public function buildAbsoluteSecureUrl($params = array(), $name = 'default', $reset = true)
    {
        $scheme = Ld_Plugin::applyFilters('Ld_Admin::scheme', Ld_Utils::getCurrentScheme(), 'secure');
        return $scheme . '://' . $this->getSite()->getHost($this->domain) . $this->buildUrl($params, $name, $reset);
    }

    public function buildAbsoluteUrl($params = array(), $name = 'default', $reset = true)
    {
        $scheme = Ld_Plugin::applyFilters('Ld_Admin::scheme', Ld_Utils::getCurrentScheme(), 'normal');
        return $scheme . '://' . $this->getSite()->getHost($this->domain) . $this->buildUrl($params, $name, $reset);
    }

    public function getUrl()
    {
        return $this->buildUrl();
    }

    public function getAcl()
    {
        if (isset($this->_acl)) {
            return $this->_acl;
        }

        $acl = new Zend_Acl();

        $guest = new Zend_Acl_Role('guest');
        $acl->addRole($guest);
        $user = new Zend_Acl_Role('user');
        $acl->addRole($user, $guest);
        $admin = new Zend_Acl_Role('admin');
        $acl->addRole($admin, $user);

        $resources = array('instances', 'repositories', 'databases', 'users', 'plugins', 'sites', 'domains', 'locales');
        foreach ($resources as $resource) {
            $acl->add( new Zend_Acl_Resource($resource) );
            $acl->allow('admin', $resource, 'manage');
        }

        $acl->allow('admin', null, 'admin');
        $acl->allow('user', 'instances', 'view');
        $acl->allow('admin', 'instances', 'update');

        Ld_Plugin::doAction('Slotter:acl', $acl);
        Ld_Plugin::doAction('Admin:acl', $acl);

        return $this->_acl = $acl;
    }

    public function userCan($action, $ressource = null)
    {
        $acl = self::getAcl();
        $userRole = self::getUserRole();
        return $acl->isAllowed($userRole, $ressource, $action);
    }

    public function getOpenidDir()
    {
        $openidDirectory = $this->getAbsolutePath() . '/openid';
        Ld_Files::createDirIfNotExists($openidDirectory);
        return $openidDirectory;
    }

    public function getLoginUrl($params = array())
    {
        return $this->getAuthUrl($params, 'login');
    }

    public function getAuthUrl($params = array(), $action = 'login')
    {
        $loginUrl = $this->buildAbsoluteSecureUrl(array('module' => 'default', 'controller' => 'auth', 'action' => $action), 'default', false);
        if (isset($params['referer']) && $params['referer']) {
            $currentUrl = Ld_Utils::getCurrentUrl(array('ld_referer', 'ref'));
            if (false === strpos($currentUrl, 'auth/login')) {
                $loginUrl .= '?ref=' . base64_encode($currentUrl);
            }
        }
        return $loginUrl;
    }

    public function getOpenidAuthUrl()
    {
        return $this->buildAbsoluteSecureUrl(array('module' => 'identity', 'controller' => 'openid', 'action' => 'auth'), 'default', false);
    }

    public function getIdentityUrl($user)
    {
        if (is_string($user)) {
            $user = $this->getSite()->getUser($user);
        }
        $id = isset($user['username']) ? $user['username'] : $user['id'];
        return $this->buildAbsoluteUrl(array('id' => $id), 'vanity');
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
