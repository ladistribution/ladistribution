<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Repository
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Repository_Remote extends Ld_Repository_Abstract
{

    public $endpoint = null;

    public function __construct($params = array())
    {
        if (is_array($params)) {
            $this->id = $params['id'];
            $this->type = $params['type'];
            $this->name = $params['name'];
            $this->endpoint = $params['endpoint'];
        }

        if (Zend_Registry::isRegistered('cache')) {
            $this->_cache = Zend_Registry::get('cache');
        }

        $this->httpClient = new Zend_Http_Client();
        $this->httpClient->setHeaders('Accept', 'application/json');
    }

    public function getUrl()
    {
        return $this->endpoint;
    }

    public function getPackages()
    {
        $cacheKey = 'Ld_Repository_Remote_Packages_' . md5($this->endpoint);

        if (isset($this->_cache)) {
            $this->packages = $this->_cache->load($cacheKey);
        }

        if (empty($this->packages)) {

            $this->packages = array();

            $this->httpClient->setUri($this->endpoint . '/packages.json');
            $response = $this->httpClient->request();
            $result = Zend_Json::decode( $response->getBody() );

            foreach ($result as $id => $params) {
                $this->packages[$id] = $this->getPackage($params);
            }

            if (isset($this->_cache)) {
                $this->_cache->save($this->packages, $cacheKey); 
            }

        }

        return $this->packages;
    }

    public function getPackage($params = array())
    {
        $package = new Ld_Package();
        $package->setInfos($params);
        return $package;
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
