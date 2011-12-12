<?php

require_once 'Ld/Services/lib/oauth2.php';

abstract class Ld_Services_Oauth2 extends Ld_Services_Base
{

    protected $_serviceName = null;

    protected $_consumer = null;

    protected $_dataStore = null;

    protected $_authorizeUrl = null;

    protected $_accessTokenUrl = null;

    protected $_scope = null;

    public function getScope()
    {
        return $this->_scope;
    }

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
                $this->_authorizeUrl, $this->_accessTokenUrl
            );
            $dataStore = $this->_getDataStore();
            $scope = $this->getScope();
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
