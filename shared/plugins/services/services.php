<?php

class Ld_Plugin_Services
{

    public function infos()
    {
        return array(
            'name' => 'Services',
            'url' => 'http://ladistribution.net/wiki/plugins/#services',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.6.30',
            'description' => Ld_Translate::notranslate('No description yet.'),
            'license' => 'MIT / GPL'
        );
    }

    public function load()
    {
        Ld_Plugin::addFilter('Identity:services', array($this, 'services'));
        // Ld_Plugin::addFilter('Ld_Controller_Action_Helper_Auth:callback', array($this, 'callback'), 10, 2);
        // Ld_Plugin::addFilter('Ld_Controller_Action_Helper_Auth:login', array($this, 'login'), 10, 2);
    }

    public function getSite()
    {
        return isset($this->_site) ? $this->_site : $this->_site = Zend_Registry::get('site');
    }

    public function login($result = null, $request = null)
    {
        $params = $request->getParams();
        if (isset($params['ld_auth_username'])) {
            try {
                $uri = Zend_Uri::factory($params['ld_auth_username']);
            } catch (Exception $e) {
                return $result;
            }
            if ($uri->getHost() == 'www.facebook.com') {
                 $auth = Zend_Auth::getInstance();
                 $adapter = new Ld_Services_Auth_Adapter_Facebook();
                 $adapter->setIdentityUrl($params['ld_auth_username']);
                 $adapter->setCallbackUrl( $this->getSite()->getAdmin()->getLoginUrl() );
                 return $auth->authenticate($adapter);
             }
        }
    }

    public function callback($result = null, $request = null)
    {
        $params = $request->getParams();
        if (isset($params['code']) && isset($params['state'])) {
            $auth = Zend_Auth::getInstance();
            $adapter = new Ld_Services_Auth_Adapter_Facebook();
            if ($adapter->isFacebookCallback()) {
                return $auth->authenticate($adapter);
            }
        }
    }

    public function getSupportedServices()
    {
        $supportedServices = array(
            'facebook'   => 'Facebook',
            'twitter'    => 'Twitter',
            'google'     => 'Google',
            'linkedin'   => 'LinkedIn',
            'flickr'     => 'Flickr',
            'tumblr'     => 'Tumblr',
            'github'     => 'GitHub',
            'identica'   => 'Identi.ca',
            'soundcloud' => 'SoundCloud',
            'foursquare' => 'Foursquare',
            'vimeo'      => 'Vimeo',
            'readmill'   => 'Readmill',
            'angellist'  => 'AngelList'
        );
        return $supportedServices;
    }

    public function services($services = array())
    {
        $services['ladistribution'] = array(
            'id' => 'ladistribution', 'name' => 'La Distribution (.net)'
        );
        $supportedServices = $this->getSupportedServices();
        foreach ($supportedServices as $service => $name) {
            $key = 'sservices_' . $service . '_enabled';
            if ($this->getSite()->getConfig($key, false)) {
                $services[$service] = array('id' => $service, 'name' => $name);
            }
        }
        return $services;
    }

    public function preferences()
    {
        $preferences = array();

        foreach ($this->getSupportedServices() as $id => $name) {
            $preferences[] = array(
                'name' => "sservices_" . $id . "_enabled", 'label' => Ld_Translate::notranslate("Enable $name support"),
                'type' => 'boolean', 'defaultValue' => false
            );
            if ($this->getSite()->getConfig("sservices_" . $id . "_enabled", false)) {
                $preferences[] = array(
                    'name' => $id . "_consumer_key", 'label' => Ld_Translate::notranslate("$name ID/Key"),
                    'type' => 'text', 'defaultValue' => ''
                );
                $preferences[] = array(
                    'name' => $id . '_consumer_secret', 'label' => Ld_Translate::notranslate("$name secret"),
                    'type' => 'text', 'defaultValue' => ''
                );
            }
        }

        // https://code.google.com/apis/console
        // https://developers.facebook.com/apps
        // https://dev.twitter.com/apps/new
        // http://www.tumblr.com/oauth/apps
        // https://github.com/account/applications
        // https://www.linkedin.com/secure/developer
        // http://www.flickr.com/services/apps/create
        // http://www.flickr.com/services/apps/create
        // http://soundcloud.com/you/apps
        // https://foursquare.com/oauth/
        // http://vimeo.com/api/applications
        // http://readmill.com/you/apps

        return $preferences;
    }

}
