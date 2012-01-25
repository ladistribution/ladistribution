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
            'version' => '0.6.33',
            'description' => Ld_Translate::notranslate('No description yet.'),
            'license' => 'MIT / GPL'
        );
    }

    public function load()
    {
        Ld_Plugin::addFilter('Identity:services', array($this, 'services'));
        Ld_Plugin::addFilter('Ld_Controller_Action_Helper_Auth:callback', array($this, 'callback'), 10, 2);
        Ld_Plugin::addFilter('Ld_Controller_Action_Helper_Auth:login', array($this, 'loginHelper'), 10, 2);
        Ld_Plugin::addAction('Ld_Auth_Login::input', array($this, 'loginForm'), 10, 1);
        Ld_Plugin::addAction('Ld_Auth_Controller:login', array($this, 'loginController'), 10, 1);
    }

    public function getSite()
    {
        return isset($this->_site) ? $this->_site : $this->_site = Zend_Registry::get('site');
    }

    public function loginHelper($result = null, $request = null)
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

    public function loginController($request = null)
    {
        $params = $request->getParams();
        // Redirect ...
        if (isset($params['ld_auth_service'])) {
            if ($params['ld_auth_service'] == 'facebook') {
                $loginUrl = $this->getSite()->getAdmin()->getLoginUrl();
                $service = new Ld_Services_Facebook();
                $redirectUrl = $service->getLoginUrl($loginUrl);
                header("Location:" . $redirectUrl);
                exit;
            }
        }
    }

    public function callback($result = null, $request = null)
    {
        // whitelist certain pages?
        // if (strpos($_SERVER['REQUEST_URI'], '/auth/login') === false) {
        //     return $result;
        // }
        // or better blacklist?
        if (strpos($_SERVER['REQUEST_URI'], '/identity/accounts') !== false) {
            return $result;
        }
        $auth = Zend_Auth::getInstance();
        $facebook = new Ld_Services_Auth_Adapter_Facebook();
        if ($facebook->isCallback()) {
            return $auth->authenticate($facebook);
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
        // $services['ladistribution'] = array(
        //     'id' => 'ladistribution', 'name' => 'La Distribution (.net)'
        // );
        $supportedServices = $this->getSupportedServices();
        foreach ($supportedServices as $service => $name) {
            $key = 'sservices_' . $service . '_enabled';
            if ($this->getSite()->getConfig($key, false)) {
                $services[$service] = array('id' => $service, 'name' => $name);
            }
        }
        return $services;
    }

    public function loginForm($view = null)
    {
        $baseUrl = $this->getSite()->getUrl('shared') . '/plugins/services';
        $view->headLink()->appendStylesheet($baseUrl . '/auth-buttons/auth-buttons.css', 'screen');
        $user = isset($view->ld_auth_user) ? $view->ld_auth_user : null;
        if (empty($user)) {
            echo '<div id="ld-login-services-buttons">';
            $loginUrl = $this->getSite()->getAdmin()->getLoginUrl();
            if ($this->getSite()->getConfig('sservices_facebook_enabled', false)) {
                echo '<a style="display:block;margin:10px auto 20px" class="btn-auth btn-facebook" href="' .
                    $loginUrl . '?ld_auth_service=facebook">with <b>Facebook</b></a>';
            }
            echo '</div>';
        }
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
