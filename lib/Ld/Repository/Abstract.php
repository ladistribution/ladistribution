<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Repository
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

abstract class Ld_Repository_Abstract
{

    public $id = null;

    public $name = null;

    public $type = null;

    public $locked = false;

    protected $types = array(
        'applications'  => array('application', 'bundle'),
        'libraries'     => array('shared', 'lib', 'css', 'js'),
        'extensions'    => array('theme', 'plugin', 'locale')
    );

    abstract public function getUrl();

    public function getPackages()
    {
        if (Zend_Registry::isRegistered('cache')) {
            $cacheKey =  $this->getCacheKey();
            $this->_cache = Zend_Registry::get('cache');
            if (isset($cacheKey, $this->_cache)) {
                $this->packages = $this->_cache->load($cacheKey);
            }
        }

        if (empty($this->packages)) {
            $this->packages = array();
            $packages = $this->getPackagesJson();
            foreach ((array)$packages as $id => $params) {
                $package = new Ld_Package($params);
                if ($this->type == 'local') {
                    $package->setAbsoluteFilename($this->getPackageDirectory($package) . "/$package->id.zip");
                }
                $this->packages[$id] = $package;
            }
            if (isset($cacheKey, $this->_cache)) {
                $this->_cache->save($this->packages, $cacheKey);
            }
        }

        return $this->packages;
    }

    public function getApplications()
    {
        return $this->_getPackagesByType('applications');
    }

    public function getLibraries()
    {
        return $this->_getPackagesByType('libraries');
    }

    public function getExtensions()
    {
        return $this->_getPackagesByType('extensions');
    }

    protected function _getPackagesByType($type)
    {
        $packages = $this->getPackages();
        $list = array();
        foreach ($packages as $id => $package) {
            if (in_array($package->type, $this->types[$type])) {
                $list[$id] = $package;
            }
        }
        return $list;
    }

    public function getPackageExtensions($packageId, $type = null)
    {
        $packages = $this->getPackages();
        $extensions = array();
        foreach ($packages as $id => $package) {
            if (isset($package->extend) && $package->extend == $packageId) {
                if (empty($type) || $type == $package->type) {
                    $extensions[$id] = $package;
                }
            }
        }
        return $extensions;
    }

}
