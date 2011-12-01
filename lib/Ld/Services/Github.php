<?php

require_once 'Ld/Services/oauth2.php';

class Ld_Services_Github extends Ld_Services_Base
{

    protected $_serviceName = 'github';

    protected $_consumer = null;

    protected $_dataStore = null;

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
                'https://github.com/login/oauth/authorize',
                'https://github.com/login/oauth/access_token'
            );
            $dataStore = $this->_getDataStore();
            $scope = 'user';
            $this->_consumer = new OAuth2_Service($client, $configuration, $dataStore, $scope);
        }
        return $this->_consumer;
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
        return $this->request('https://api.github.com/user');
    }

    public function getIdentity()
    {
        $gUser = $this->_getUser();
        $identity = array(
            'guid' => 'github:' . $gUser['id'],
            'url' => $gUser['html_url'],
            'username' => $gUser['login'],
            'fullname' => $gUser['name'],
            'location' => $gUser['location'],
            'email' => $gUser['email'],
            'avatar_url' => $gUser['avatar_url'],
            'created_at' => $gUser['created_at'],
        );
        return $identity;
    }

    public function setToken($token)
    {
        $dataStore = $this->_getDataStore();
        $dataStore->storeAccessToken( new OAuth2_Token($token['accessToken'], $token['refreshToken'], $token['lifeTime']) );
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

}
