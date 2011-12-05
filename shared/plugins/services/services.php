<?php

class Ld_Plugin_Services
{

    public function infos()
    {
        return array(
            'name' => 'Social Services',
            'url' => 'http://ladistribution.net/wiki/plugins/#services',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.6-14',
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

    public function services($services = array())
    {
        $supportedServices = array(
            'google'    => 'Google',
            'facebook'  => 'Facebook',
            'twitter'   => 'Twitter',
            'github'    => 'Github'
        );
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

        // https://code.google.com/apis/console

        $preferences[] = array(
            'name' => 'sservices_google_enabled', 'label' => Ld_Translate::notranslate('Enable Google support'),
            'type' => 'boolean', 'defaultValue' => false
        );
        if ($this->getSite()->getConfig('sservices_google_enabled', false)) {
            $preferences[] = array(
                'name' => 'google_consumer_key', 'label' => Ld_Translate::notranslate('Google Client ID'),
                'type' => 'text', 'defaultValue' => ''
            );
            $preferences[] = array(
                'name' => 'google_consumer_secret', 'label' => Ld_Translate::notranslate('Google Client secret:'),
                'type' => 'text', 'defaultValue' => ''
            );
        }

        // https://developers.facebook.com/apps

        $preferences[] = array(
            'name' => 'sservices_facebook_enabled', 'label' => Ld_Translate::notranslate('Enable Facebook support'),
            'type' => 'boolean', 'defaultValue' => false
        );
        if ($this->getSite()->getConfig('sservices_facebook_enabled', false)) {
            $preferences[] = array(
                'name' => 'facebook_consumer_key', 'label' => Ld_Translate::notranslate('Facebook App ID'),
                'type' => 'text', 'defaultValue' => ''
            );
            $preferences[] = array(
                'name' => 'facebook_consumer_secret', 'label' => Ld_Translate::notranslate('Facebook App Secret'),
                'type' => 'text', 'defaultValue' => ''
            );
        }

        // https://dev.twitter.com/apps/new

        $preferences[] = array(
            'name' => 'sservices_twitter_enabled', 'label' => Ld_Translate::notranslate('Enable Twitter support'),
            'type' => 'boolean', 'defaultValue' => false
        );
        if ($this->getSite()->getConfig('sservices_twitter_enabled', false)) {
            $preferences[] = array(
                'name' => 'twitter_consumer_key', 'label' => Ld_Translate::notranslate('Twitter Consumer Key'),
                'type' => 'text', 'defaultValue' => ''
            );
            $preferences[] = array(
                'name' => 'twitter_consumer_secret', 'label' => Ld_Translate::notranslate('Twitter Consumer Secret'),
                'type' => 'text', 'defaultValue' => ''
            );
        }

        // http://www.tumblr.com/oauth/register

        // $preferences[] = array(
        //     'name' => 'sservices_tumblr_enabled', 'label' => Ld_Translate::notranslate('Enable Tumblr support'),
        //     'type' => 'boolean', 'defaultValue' => false
        // );
        // if ($this->getSite()->getConfig('sservices_tumblr_enabled', false)) {
        //     $preferences[] = array(
        //         'name' => 'tumblr_consumer_key', 'label' => Ld_Translate::notranslate('Tumblr Consumer Key'),
        //         'type' => 'text', 'defaultValue' => ''
        //     );
        //     $preferences[] = array(
        //         'name' => 'tumblr_consumer_secret', 'label' => Ld_Translate::notranslate('Tumblr Secret Key'),
        //         'type' => 'text', 'defaultValue' => ''
        //     );
        // }

        // https://github.com/account/applications/new

        $preferences[] = array(
            'name' => 'sservices_github_enabled', 'label' => Ld_Translate::notranslate('Enable Github support'),
            'type' => 'boolean', 'defaultValue' => false
        );
        if ($this->getSite()->getConfig('sservices_github_enabled', false)) {
            $preferences[] = array(
                'name' => 'github_consumer_key', 'label' => Ld_Translate::notranslate('Github Client ID'),
                'type' => 'text', 'defaultValue' => ''
            );
            $preferences[] = array(
                'name' => 'github_consumer_secret', 'label' => Ld_Translate::notranslate('Github Secret'),
                'type' => 'text', 'defaultValue' => ''
            );
        }

        return $preferences;
    }

}
