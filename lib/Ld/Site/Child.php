<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Site
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Site_Child extends Ld_Site_Local
{

    protected $_parent = null;

    public function __construct($params = array(), $parent = null)
    {
        if ($parent) {
            $this->setParentSite($parent);
        }
        parent::__construct($params);
    }

    public function init()
    {
        $this->_checkDirectories();
        $this->_checkConfig();
        $this->_checkRoot();
    }

    public function isChild()
    {
        return true;
    }

    public function setParentSite($parent)
    {
        $this->_parent = $parent;
    }

    public function getParentSite()
    {
        if (empty($this->_parent)) {
            throw new Exception('No parent site defined');
        }
        return $this->_parent;
    }

    public function hasParentSite()
    {
        return isset($this->_parent);
    }

    public function getRepositories($type = null)
    {
        return $this->getParentSite()->getRepositories($type);
    }

    public function getDatabases($type = null)
    {
        return $this->getParentSite()->getDatabases($type);
    }

    public function getLocales()
    {
        return $this->getParentSite()->getLocales();
    }

    public function getHost($domain = null)
    {
        $host = $this->getParentSite()->getHost();
        return $host;
    }

    public function getRelativeUrl($dir = null)
    {
        switch ($dir) {
            case 'js':
            case 'css':
                return $this->getParentSite()->getRelativeUrl($dir);
            default:
                return parent::getRelativeUrl($dir);
        }
    }

    public function getLibraryInfos($id)
    {
        return $this->getParentSite()->getLibraryInfos($id);
    }

    public function createInstance($packageId, $preferences = array())
    {
        // if it's a library (weak test)
        if (empty($preferences)) {
            return $this->getParentSite()->createInstance($packageId);
        }
        // else
        return parent::createInstance($packageId, $preferences);
    }

    public function getDirectory($dir = null)
    {
        switch ($dir) {
            case 'lib':
            case 'shared':
            case 'tmp':
            case 'cache':
            case 'js':
            case 'css':
            case 'repositories':
                return $this->getParentSite()->getDirectory($dir);
            default:
                return parent::getDirectory($dir);
        }
    }

    public function getConfig($key = null, $default = null)
    {
        $config = $this->_config;
        if (empty($config)) {
            $config = $this->_config = $this->getModel('config')->getConfig();
        }

        if ($this->hasParentSite()) {
            $config['open_registration'] = isset($config['open_registration']) && $this->getParentSite()->getConfig('open_registration') == 1 ? $config['open_registration'] : 0;
        }

        if (isset($key)) {
            $value = isset($config[$key]) ? $config[$key] : $default;
            return $value;
        }

        return $config;
    }

    public function getModel($model)
    {
        switch ($model) {
            case 'users':
                return $this->getParentSite()->getModel($model);
            default:
                return parent::getModel($model);
        }
    }

    /* Users */

    public function getUsers($params = array())
    {
        echo "getUsers should not be called without a good reason. never.<br>";
        $users = $this->getParentSite()->getUsers($params);
        return $users;
    }

    public function addUser($user, $validate = true)
    {
        $user = $this->getParentSite()->addUser($user, $validate);
        // Set User Role in Admin
        if ($admin = $this->getAdmin()) {
            $roles = array_merge($admin->getUserRoles(), array($user['username'] => 'user'));
            $admin->setUserRoles($roles);
        }
        return $user;
        // throw new Exception('addUser: not available in Child sites');
    }

    public function updateUser($username, $infos = array())
    {
        return $this->getParentSite()->updateUser($username, $infos);
        // throw new Exception('updateUser: not available in Child sites');
    }

    public function deleteUser($username)
    {
        throw new Exception('deleteUser: not available in Child sites');
    }

    public function getUser($username)
    {
        return $this->getParentSite()->getUser($username);
    }

    public function getDomains()
    {
        return $this->getParentSite()->getDomains();
    }

    // Colors

    public function getColors()
    {
        $default = $this->getParentSite()->getColors();
        $stored = Ld_Files::getJson($this->getDirectory('dist') . '/colors.json');
        $colors = Ld_Ui::computeColors($default, $stored);
        return $colors;
    }

}
