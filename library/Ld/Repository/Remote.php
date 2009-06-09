<?php

require_once 'Ld/Repository/Abstract.php';

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

        if (Zend_registry::isRegistered('cache')) {
            $this->_cache = Zend_registry::get('cache');
        }

        $this->httpClient = new Zend_Http_Client();
        $this->httpClient->setHeaders('Accept', 'application/json');
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
        $packages = $this->getPackages();
        $applications = array();
        foreach ($packages as $id => $package) {
            if ($package->type == 'application') {
                $applications[$id] = $package;
            }
        }
        return $applications;
    }

    public function getLibraries()
    {
        $packages = $this->getPackages();
        $libraries = array();
        foreach ($packages as $id => $package) {
            if (in_array($package->type, $this->types['libraries'])) {
                $libraries[$id] = $package;
            }
        }
        return $libraries;
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
