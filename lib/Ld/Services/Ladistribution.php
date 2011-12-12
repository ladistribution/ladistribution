<?php

class Ld_Services_Ladistribution extends Ld_Services_Oauth2
{

    protected $_serviceName = 'ladistribution';

    protected $_serviceHost = 'ladistribution.net';

    protected $_baseUrl = 'http://ladistribution.net';

    protected $_scope = 'openid profile email';

    public function __construct()
    {
        $this->_authorizeUrl = $this->_baseUrl . '/api/oauth/authorize';
        $this->_accessTokenUrl = $this->_baseUrl . '/api/oauth/token';
    }

    public function getOauthKeys()
    {
        $host = $this->_serviceHost;
        $keys = $this->getSite()->getConfig('oauth_keys', array());
        if (empty($keys[$host])) {
            $params = array(
                'type' => 'client_associate',
                'application_name' => $this->getSite()->getName(),
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

}
