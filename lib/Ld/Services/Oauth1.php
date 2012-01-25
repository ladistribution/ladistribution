<?php

abstract class Ld_Services_Oauth1 extends Ld_Services_Base
{

    protected $_serviceName = null;

    protected $_consumer = null;

    protected $_httpClient = null;

    protected $_authorizeUrl = null;

    protected $_requestTokenUrl = null;

    protected $_accessTokenUrl = null;

    public function _getConfig()
    {
        $config = array();
        $config['authorizeUrl'] = $this->_authorizeUrl;
        $config['requestTokenUrl'] = $this->_requestTokenUrl;
        $config['accessTokenUrl'] = $this->_accessTokenUrl;
        $config['consumerKey'] = $this->getClientId();
        $config['consumerSecret'] = $this->getClientSecret();
        $config['callbackUrl'] = $this->getCallbackUrl();
        return $config;
    }

    public function getSession()
    {
        if (empty($this->_session)) {
            $this->_session = new Zend_Session_Namespace('Ld_Services_' . ucfirst($this->_serviceName));
        }
        return $this->_session;
    }

    public function _getConsumer()
    {
        if (empty($this->_consumer)) {
            $config = $this->_getConfig();
            $this->_consumer = new Zend_Oauth_Consumer($config);
        }
        return $this->_consumer;
    }

    public function _getHttpClient()
    {
        // if (empty($this->_httpClient)) {
            $token = $this->_getAccessToken();
            $config = $this->_getConfig();
            $this->_httpClient = $token->getHttpClient($config);
        // }
        return $this->_httpClient;
    }

    public function authorize()
    {
        $consumer = $this->_getConsumer();
        $token = $consumer->getRequestToken();

        $session = $this->getSession();
        $session->token = serialize($token);

        $consumer->redirect();
    }

    public function callback()
    {
        $consumer = $this->_getConsumer();

        $session = $this->getSession();
        $token = unserialize($session->token);

        $this->_accessToken = $consumer->getAccessToken($_GET, $token);
    }

    public function _getAccessToken()
    {
        if (empty($this->_accessToken)) {
            throw new Exception('No Access Token defined.');
        }
        return $this->_accessToken;
    }

    public function setToken($token)
    {
        $accessToken = new Zend_Oauth_Token_Access();
        $accessToken->setToken($token['token']);
        $accessToken->setTokenSecret($token['token_secret']);

        $this->_accessToken = $accessToken;
    }

    public function getToken()
    {
        $accessToken = $this->_getAccessToken();
        return array(
            'token' => $accessToken->getToken(),
            'token_secret' => $accessToken->getTokenSecret()
         );
    }

    public function _getCacheKey($url, $method = 'GET', $params = array())
    {
        $token = $this->getToken();
        return get_class($this) . md5($method . $url . serialize($params) . $token['token']);
    }

}
