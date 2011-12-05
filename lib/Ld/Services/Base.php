<?php

abstract class Ld_Services_Base
{

    protected $_serviceName = null;

    protected $_clientId = null;

    protected $_clientSecret = null;

    public function __construct($params = array())
    {
        if (isset($params['clientId'])) {
            $this->setClientId($params['clientId']);
        }
        if (isset($params['clientSecret'])) {
            $this->setClientSecret($params['clientSecret']);
        }
    }

    public function getSite()
    {
        return isset($this->_site) ? $this->_site : $this->_site = Zend_Registry::get('site');
    }

    public function getServiceName()
    {
        return $this->_serviceName;
    }

    public function getClientId()
    {
        if (isset($this->_clientId)) {
            return $this->_clientId;
        }
        if ($clientId = $this->getSite()->getConfig($this->getServiceName() . '_consumer_key')) {
            return $clientId;
        }
    }

    public function getClientSecret()
    {
        if (isset($this->_clientSecret)) {
            return $this->_clientSecret;
        }
        if ($clientSecret = $this->getSite()->getConfig($this->getServiceName() . '_consumer_secret')) {
            return $clientSecret;
        }
    }

    public function setClientId($clientId)
    {
        $this->_clientId = $clientId;
    }

    public function setClientSecret($clientSecret)
    {
        $this->_clientSecret = $clientSecret;
    }

    public function isConfigured()
    {
        return $this->getClientId() && $this->getClientSecret();
    }

    public function getCallbackUrl()
    {
        $baseUrl = $this->getSite()->getAdmin()->buildAbsoluteSecureUrl(array(
            'module' => 'identity', 'controller' => 'accounts', 'action' => 'callback'));
        return $baseUrl . '?service=' . $this->getServiceName();
    }

    abstract public function authorize();

    abstract public function callback();

    public function test()
    {
        try {
            $user = $this->_getUser();
            return empty($user) ? false : true;
        } catch (Exception $e) {
            return false;
        }
    }

    abstract public function getToken();

    abstract public function setToken($token);

    public function getAccessToken()
    {
        $token = $this->getToken();
        return $token['accessToken'];
    }

    public function _getCacheKey($url, $method = 'GET', $params = array())
    {
        return get_class($this) . '_' . md5($method . $url . serialize($params) . $this->getAccessToken());
    }

    public function _getHttpClient()
    {
        $httpClient = new Zend_Http_Client();
        $httpClient->setConfig(array('timeout' => 10, 'useragent' => 'La Distribution SServices'));
        $httpClient->setHeaders('Authorization', "Bearer " . $this->getAccessToken());
        return $httpClient;
    }

    public function _makeRequest($url, $method = 'GET', $params = array())
    {
        $client = $this->_getHttpClient();
        $client->setUri($url);
        foreach ($params as $key => $value) {
            $client->setParameterGet($key, $value);
        }
        $response = $client->request($method);
        $body = $response->getBody();
        $result = Zend_Json::decode($body, Zend_Json::TYPE_ARRAY);
        if (isset($result->error)) {
            throw new Exception($result->error);
        }
        return $result;
    }

    public function request($url, $method = 'GET', $params = array())
    {
        if (Zend_Registry::isRegistered('cache')) {
            $cache = Zend_Registry::get('cache');
            $cacheKey = $this->_getCacheKey($url);
            $result = $cache->load($cacheKey);
        }
        if (empty($result)) {
            $result = $this->_makeRequest($url);
            if ($cache) {
                $cache->save($result, $cacheKey);
            }
        }
        return $result;
    }

}
