<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Site
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Site_Child extends Ld_Site_Local
{

    protected $_parent = null;

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

    public function getUrl($dir = null)
    {
        $domain = !empty($this->domain) ? $this->domain : null;
        switch ($dir) {
            case 'js':
            case 'css':
                return $this->getParentSite()->getUrl($dir, $domain);
            default:
                return parent::getUrl($dir, $domain);
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

    /* Users */

    public function getUsers($params = array())
    {
        echo "getUsers should not be called without a good reason. never.<br>";
        $users = $this->getParentSite()->getUsers($params);
        return $users;
    }

    public function addUser($user, $validate = true)
    {
        return $this->getParentSite()->addUser($user, $validate);
        throw new Exception('addUser: not available in Child sites');
    }

    public function updateUser($username, $infos = array())
    {
        throw new Exception('updateUser: not available in Child sites');
    }

    public function deleteUser($username)
    {
        throw new Exception('deleteUser: not available in Child sites');
    }

    public function getUser($username)
    {
        return $this->getParentSite()->getUser($username);
    }

    public function getUserByUrl($url)
    {
        return $this->getParentSite()->getUserByUrl($url);
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
