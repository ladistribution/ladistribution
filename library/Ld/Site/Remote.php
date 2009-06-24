<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Site
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Site_Remote extends Ld_Site_Abstract
{

    public function __construct($params = array())
    {
        $this->httpClient = new Zend_Http_Client();

        if (isset($params['username']) && isset($params['password'])) {
            $this->httpClient->setAuth($params['username'], $params['password']);
        }

        $this->httpClient->setHeaders('Accept', 'application/json');

        $this->id = $params['id'];
        $this->type = $params['type'];
        $this->name = $params['name'];

        $this->baseUrl = $params['endpoint'];

        $siteInfos = $this->getInfos();
        $this->slots = $siteInfos['slots'];
        $this->availableSlots = $siteInfos['availableSlots'];
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getInfos()
    {
        $this->httpClient->setUri($this->baseUrl);
        $response = $this->httpClient->request();
        if ($response->isError()) {
            throw new Exception("HTTP Error. Maybe authentication issue ?");
        }
        $result = Zend_Json::decode( $response->getBody() );
        return $result['site'];
    }
    
    public function getInstances()
    {
        $this->httpClient->setUri($this->baseUrl);
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        return $result['instances'];
    }

    public function getInstance($id)
    {
        $instance = new Ld_Instance_Application_Remote();
        $instance->setPath($id);
        $instance->setSite($this);
        return $instance;
    }

    public function deleteInstance($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance->getPath() . '/delete');
        $response = $this->httpClient->request('POST');
    }

    public function updateInstance($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance->getPath() . '/update');
        $response = $this->httpClient->request('POST');
    }

    public function createInstance($packageId, $preferences = array())
    {
        $this->httpClient->setUri($this->baseUrl . '/instances/new?packageId=' . $packageId);
        $parameters = Zend_Json::encode(array('preferences' => $preferences));
        $response = $this->httpClient->setRawData($parameters, 'application/json')->request('POST');
    }

    /* Backups */

    public function getBackups($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/restore');
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        return $result['archives'];
    }

    public function restoreBackup($instance, $archive)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/restore');
        $parameters = Zend_Json::encode(array('archive' => $archive));
        $response = $this->httpClient->setRawData($parameters, 'application/json')->request('POST');
    }

    /* Databases */

    public function getDatabases()
    {
        return array();
    }

    /* Users */

    public function getUsers()
    {
        return array();
    }

    /* Repositories */

    public function getRepositories()
    {
        return array();
    }

    /* Packages */

    public function getPackages()
    {
        $this->httpClient->setUri($this->baseUrl . '/packages');
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        
        $packages = array();
        foreach ($result['packages'] as $id => $infos) {
            $package = new Ld_Package();
            $package->setInfos($infos);
            $packages[$id] = $package;
        }
        return $packages;
    }

    public function getPackageExtensions($packageId, $type = null)
    {
        $uri = $this->baseUrl . '/packages/extensions/id/' . $packageId;
        if (isset($type)) {
            $uri .= '/type/' . $type;
        }
        $this->httpClient->setUri($uri);
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        
        $packages = array();
        foreach ($result['packages'] as $id => $infos) {
            $package = new Ld_Package();
            $package->setInfos($infos);
            $packages[$id] = $package;
        }
        return $packages;
    }

    public function getInstallPreferences($package)
    {
        if (is_object($package)) {
            $package = $package->id;
        }

        $this->httpClient->setUri($this->baseUrl . '/packages/preferences/type/install/id/' . $package);
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );

        return $result['preferences'];
    }

}
