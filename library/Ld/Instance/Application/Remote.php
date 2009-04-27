<?php

class Ld_Instance_Application_Remote extends Ld_Instance_Application_Abstract
{

    protected function _getRemote($action)
    {
        $uri = $this->site->getBaseUrl() . '/' . $this->path . '/' . $action;
        
        // $client = new Ld_Http_Client($uri);
        // return $client->getJson();
        
        $this->site->getHttpClient()->setUri($uri);
        $response = $this->site->getHttpClient()->request();
        $result = Zend_Json::decode( $response->getBody() );
        return $result;
    }

    protected function _postRemote($action, $parameters = array())
    {
        $uri = $this->site->getBaseUrl() . '/' . $this->path . '/' . $action;
        
        // $client = new Ld_Http_Client($uri);
        // return $client->postJson($parameters);
        
        $this->site->getHttpClient()
            ->setUri($uri)
            ->setRawData(Zend_Json::encode($parameters), 'application/json');
        $response = $this->site->getHttpClient()->request('POST');
        $result = Zend_Json::decode( $response->getBody() );
        return $result;
    }

    public function getInfos()
    {
        if (empty($this->infos)) {
            $result = $this->_getRemote('manage');
            $this->infos = $result['instance'];
        }
        return $this->infos;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setSite($site)
    {
        $this->site = $site;
    }
    
    public function getLinks()
    {
        return array();
    }

    public function getPreferences($type = 'preferences')
    {
        if ($type == 'theme') {
            if (empty($this->themePreferences)) {
                $result = $this->_getRemote('themes');
                $this->themePreferences = $result['preferences'];
            }
            return $this->themePreferences;
        }

        if (empty($this->preferences)) {
            $result = $this->_getRemote('configure');
            $this->preferences = $result['preferences'];
        }
        return $this->preferences;
    }

    /* Themes */

    public function getThemes()
    {
        if (empty($this->themes)) {
            $result = $this->_getRemote('themes');
            $this->themes = $result['themes'];
        }
        return $this->themes;
    }

    public function setTheme($theme)
    {
        $this->_postRemote('themes', array('theme' => $theme));
    }

    /* Configuration */

    public function getConfiguration($type = 'general')
    {
        if ($type == 'theme') {
            if (empty($this->themeConfiguration)) {
                $result = $this->_getRemote('themes');
                $this->themeConfiguration = $result['configuration'];
            }
            return $this->themeConfiguration;
        }

        if (empty($this->configuration)) {
            $result = $this->_getRemote('configure');
            $this->configuration = $result['configuration'];
        }
        return $this->configuration;
    }

    public function setConfiguration($configuration, $type = 'general')
    {
        if ($type == 'theme') {
            $result = $this->_postRemote('themes', array('configuration' => $configuration));
            return $result['configuration'];
        }
        
        $result = $this->_postRemote('configure', array('configuration' => $configuration));
        return $result['configuration'];
    }

    /* Extensions */

    public function getExtensions()
    {
        $extensions = array();
        
        $result = $this->_getRemote('manage');
        
        if (isset($result['extensions'])) {
            foreach ($result['extensions'] as $infos) {
                $instance = new Ld_Instance_Extension();
                $instance->setInfos($infos);
                $extensions[] = $instance;
            }
        }
        
        return $extensions;
    }

    public function addExtension($extension, $preferences = array())
    {
        $result = $this->_postRemote('extensions', array('add' => $extension, 'preferences' => $preferences));
    }

    public function updateExtension($extension)
    {
        $result = $this->_postRemote('extensions', array('update' => $extension));
    }

    public function removeExtension($extension)
    {
        $result = $this->_postRemote('extensions', array('remove' => $extension));
    }

}
