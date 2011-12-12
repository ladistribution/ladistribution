<?php

require_once 'google-api-php-client/apiClient.php';
require_once 'google-api-php-client/contrib/apiPlusService.php';

class Ld_Services_Google extends Ld_Services_Base
{

    protected $_serviceName = 'google';

    protected $_apiClient = null;

    protected $_plusService = null;

    protected $_tokenName = 'OAuth';

    public function _getApiClient()
    {
        if (empty($this->_apiClient)) {
            $this->_apiClient = new apiClient();
            $this->_apiClient->setClientId( $this->getClientId() );
            $this->_apiClient->setClientSecret( $this->getClientSecret() );
            $this->_apiClient->setRedirectUri( $this->getCallbackUrl() );

            // Add scope
            $this->_apiClient->addService('userinfo.profile', 'v1');
            $this->_apiClient->addService('userinfo.email', 'v1');

            // Contacts
            global $apiConfig;
            $apiConfig['services']['contacts'] = array(
                'scope' => array(
                    'https://www.google.com/m8/feeds',
                    'http://www-opensocial.googleusercontent.com/api/people'
                 )
            );
            $this->_apiClient->addService('contacts', 'v1');

            // Initialise the Plus service (add scope automatically)
            // - what does happen if an user is not on Google+ ?
            $plusService = $this->_getPlusService($this->_apiClient);
        }

        return $this->_apiClient;
    }

    public function _getPlusService($apiClient = null)
    {
        if (empty($this->_plusService)) {
            if (empty($apiClient)) {
                $apiClient = $this->_getApiClient();
            }
            $this->_plusService = new apiPlusService($apiClient);
        }
        return $this->_plusService;
    }

    public function authorize()
    {
        $apiClient = $this->_getApiClient();
        $apiClient->authenticate();
    }

    public function callback()
    {
        $apiClient = $this->_getApiClient();
        $apiClient->authenticate();
    }

    /* renew access_token if needed */
    public function check()
    {
        $apiClient = $this->_getApiClient();
        $apiClient->authenticate();
    }

    public function refresh()
    {
        $plusUser = $this->_getPlusUser();
        if ($user = Ld_Auth::getUser()) {
            $token = $this->getToken();
            if ($token['access_token'] != $user['identities'][$this->_serviceName]['oauth_access_token']['access_token']) {
                $user['identities'][$this->_serviceName]['oauth_access_token'] = $token;
                $this->getSite()->updateUser($user);
            }
        }
    }

    public function _getPlusUser($id = 'me')
    {
        $plus = $this->_getPlusService();
        return $plus->people->get($id);
    }

    public function _getUser()
    {
        return $this->_getPlusUser();
    }

    public function _getGoogleUser()
    {
        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $this->getAccessToken();
        $user = Ld_Http::jsonRequest($url);
        return $user;
    }

    public function getToken()
    {
        $apiClient = $this->_getApiClient();
        $accessToken = $apiClient->getAccessToken();
        if (is_string($accessToken)) {
            $accessToken = Zend_Json::decode($accessToken);
        }
        return $accessToken;
    }

    public function setToken($token)
    {
        $apiClient = $this->_getApiClient();
        $apiClient->setAccessToken( Zend_Json::encode($token) );
    }

    public function getAccessToken()
    {
        $token = $this->getToken();
        return $token['access_token'];
    }

    public function getIdentity()
    {
        $user = array();
        if ($googleUser = $this->_getGoogleUser()) {
            $user = array_merge($user, array(
                'guid' => 'google:' . $googleUser['id'],
                'email' => $googleUser['email'],
                'fullname' => $googleUser['name'],
                'gender' => $googleUser['gender'],
                'avatar_url' => $googleUser['picture']
            ));
        }
        if ($plusUser = $this->_getPlusUser()) {
            $user = array_merge($user, array(
                'guid' => 'google:' . $plusUser['id'],
                'url' => $plusUser['url'],
                'fullname' => $plusUser['displayName'],
                'avatar_url' => $plusUser['image']['url']
            ));
            if (isset($plusUser['gender'])) {
                $user['gender'] = $plusUser['gender'];
            }
        }
        return $user;
    }

    public function _normaliseUser($gUser = array())
    {
        // if (isset($gUser['id'])) {
        //     $gUser['guid'] = 'google:' . $gUser['id'];
        // }
        // if (isset($gUser['name'])) {
        //     $gUser['fullname'] = $gUser['name'];
        // }
        if (isset($gUser['email'])) {
            // check only gmail
            if (strpos($gUser['email'], '@gmail.') || strpos($gUser['email'], '@googlemail.')) {
                $x = explode('@', $gUser['email']);
                $gUser['username'] = $x[0];
            }
        }
        if (isset($gUser['email_alias']) && empty($gUser['username'])) {
            foreach ($gUser['email_alias'] as $email) {
                // check only gmail
                if (strpos($email, '@gmail.') || strpos($email, '@googlemail.')) {
                    $x = explode('@', $email);
                    $gUser['username'] = $x[0];
                }
            }
        }
        return $gUser;
    }

}
