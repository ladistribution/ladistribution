<?php

require_once 'Ld/Services/oauth2.php';

class Ld_Services_Ladistribution extends Ld_Services_Base
{

    protected $_serviceName = 'ladistribution';

    protected $_consumer = null;

    protected $_dataStore = null;

    protected $_host = 'ladistribution.net';

    protected $_baseUrl = 'http://ladistribution.net';

    public function _getDataStore()
    {
        if (empty($this->_dataStore)) {
            $this->_dataStore = $dataStore = new OAuth2_DataStore_Session();
        }
        return $this->_dataStore;
    }

    public function _getConsumer()
    {
        if (empty($this->_consumer)) {
            $client = new OAuth2_Client(
                $this->getClientId(),
                $this->getClientSecret(),
                $this->getCallbackUrl()
            );
            $configuration = new OAuth2_Service_Configuration(
                $this->_baseUrl . '/api/oauth/authorize',
                $this->_baseUrl . '/api/oauth/token'
            );
            $dataStore = $this->_getDataStore();
            $scope = "openid profile email";
            $this->_consumer = new OAuth2_Service($client, $configuration, $dataStore, $scope);
        }
        return $this->_consumer;
    }

    public function getOauthKeys()
    {
        $host = $this->_host;
        $keys = $this->getSite()->getConfig('oauth_keys', array());
        if (empty($keys[$host])) {
            $params = array(
                'type' => 'client_associate',
                'application_name' => sprintf('La Distribution (%s)', $this->getSite()->getHost()),
                'application_url' => $this->getSite()->getUrl(),
                'application_type' => 'web',
                'redirect_uri' => $this->getSite()->getUrl()
            );
            $result = Ld_Http::jsonRequest($this->_baseUrl . '/api/oauth/register', 'POST', $params);
            $keys[$host] = array('client_id' => $result['client_id'], 'client_secret' => $result['client_secret']);
            $this->getSite()->setConfig('oauth_keys', $keys);
        }
        return $keys[$host];
    }

    public function getClientId()
    {
        $keys = $this->getOauthKeys();
        return $keys['client_id'];
    }

    public function getClientSecret()
    {
        $keys = $this->getOauthKeys();
        return $keys['client_secret'];
    }

    public function authorize()
    {
        $consumer = $this->_getConsumer();
        $consumer->authorize();
    }

    public function callback()
    {
        $consumer = $this->_getConsumer();
        $consumer->getAccessToken();
    }

    public function _getUser()
    {
        $consumer = $this->_getConsumer();
        $dataStore = $this->_getDataStore();
        $token = $dataStore->retrieveAccessToken();
        $params = array('access_token' => $token->getAccessToken());
        return Ld_Http::jsonRequest($this->_baseUrl . '/api/oauth/userinfo', 'POST', $params);
    }

    public function getIdentity()
    {
        $lUser = $this->_getUser();
        $identity = array(
            'url' => $lUser['url'],
            'username' => $lUser['username'],
            'fullname' => $lUser['fullname'],
            'email' => $lUser['email'],
            'avatar_url' => $lUser['avatar_url'],
        );
        return $identity;
    }

    public function getToken()
    {
        $dataStore = $this->_getDataStore();
        $token = $dataStore->retrieveAccessToken();
        return array(
            'accessToken' => $token->getAccessToken(),
            'refreshToken' => $token->getRefreshToken(),
            'lifeTime' => $token->getLifeTime(),
            'tokenType' => 'Bearer'
        );
    }

    public function setToken($token)
    {
        $dataStore = $this->_getDataStore();
        $dataStore->storeAccessToken( new OAuth2_Token($token['accessToken'], $token['refreshToken'], $token['lifeTime']) );
    }

}
