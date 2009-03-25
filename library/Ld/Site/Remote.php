<?php

require_once 'Ld/Site/Abstract.php';

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

        $this->endpoint = $params['endpoint'];
        $this->baseUrl = $params['endpoint'];

        $siteInfos = $this->getInfos();
        $this->slots = $siteInfos['slots'];
        $this->availableSlots = $siteInfos['availableSlots'];
    }

    public function getInfos()
    {
        $this->httpClient->setUri($this->baseUrl);
        $response = $this->httpClient->request();
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
        $this->httpClient->setUri($this->baseUrl . '/' . $id . '/manage');
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );

        $result =  $result['instance'];
        $instance = new Ld_Instance_Application();
        $instance->setPath($id);
        $instance->setInfos($result['infos']);
        return $instance;
    }

    public function extendInstance($instance, $extension, $preferences)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/extensions');
        $parameters = Zend_Json::encode(array('extension' => $extension, 'preferences' => $preferences));
        $response = $this->httpClient->setRawData($parameters, 'application/json')->request('POST');
    }

    public function deleteInstance($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/delete');
        $response = $this->httpClient->request('POST');
    }

    public function updateInstance($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/update');
        $response = $this->httpClient->request('POST');
    }

    public function createInstance($packageId, $preferences = array())
    {
        $this->httpClient->setUri($this->baseUrl . '/instances/new?packageId=' . $packageId);
        $parameters = Zend_Json::encode(array('preferences' => $preferences));
        $response = $this->httpClient->setRawData($parameters, 'application/json')->request('POST');
    }

    /* Preferences */

    public function getPreferences($parameter, $type = 'preferences')
    {
        if (is_array($parameter) && isset($parameter['package'])) {
            $packageId = $parameter['package'];
            $instance = $parameter;
        } else {
            $packageId = $parameter;
        }
        switch ($type) {
            case 'install':
                $this->httpClient->setUri($this->endpoint . "/packages/$packageId/preferences?type=$type");
                break;
            case 'configuration':
                $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/configure');
                break;
            case 'theme':
                $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/themes');
                break;
        }
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        return $result['preferences'];
    }

    /* Themes */

    public function getThemes($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/themes');
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        return $result['themes'];
    }

    public function setTheme($instance, $theme)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/themes');
        $parameters = Zend_Json::encode(array('theme' => $theme));
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

    /* Configuration */

    public function getConfiguration($instance)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/configure');
        $response = $this->httpClient->request();
        $result = Zend_Json::decode( $response->getBody() );
        return $result['configuration'];
    }

    public function setConfiguration($instance, $configuration)
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/configure');
        $parameters = Zend_Json::encode(array('configuration' => $configuration));
        $response = $this->httpClient->setRawData($parameters, 'application/json')->request('POST');
        $result = Zend_Json::decode( $response->getBody() );
        return $result['configuration'];
    }

    /* Extensions */

    public function addExtension($instance, $extension, $preferences = array())
    {
        $this->_extensionAction($instance, array('add' => $extension, 'preferences' => $preferences));
    }

    public function removeExtension($instance, $extension)
    {
        $this->_extensionAction($instance, array('remove' => $extension['path']));
    }

    public function updateExtension($instance, $extension)
    {
        $this->_extensionAction($instance, array('update' => $extension['path']));
    }

    public function _extensionAction($instance, $params = array())
    {
        $this->httpClient->setUri($this->baseUrl . '/' . $instance['path'] . '/extensions');
        $parameters = Zend_Json::encode($params);
        $response = $this->httpClient->setRawData($parameters, 'application/json')->request('POST');
        $result = Zend_Json::decode( $response->getBody() );
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

}
