<?php

class Ld_Services_Twitter extends Ld_Services_Base
{

    protected $_serviceName = 'twitter';

    protected $_consumer = null;

    protected $_httpClient = null;

    public function _getConfig()
    {
        $config = array();
        $config['siteUrl'] = 'http://twitter.com/oauth';
        $config['authorizeUrl'] = 'http://twitter.com/oauth/authenticate';
        $config['consumerKey'] = $this->getClientId();
        $config['consumerSecret'] = $this->getClientSecret();
        $config['callbackUrl'] = $this->getCallbackUrl();
        return $config;
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
        if (empty($this->_httpClient)) {
            $token = $this->_getAccessToken();
            $config = $this->_getConfig();
            $this->_httpClient = $token->getHttpClient($config);
        }
        return $this->_httpClient;
    }

    public function authorize()
    {
        $consumer = $this->_getConsumer();
        $_SESSION['twitter_request_token'] = $consumer->getRequestToken();
        $consumer->redirect();
    }

    public function callback()
    {
        $consumer = $this->_getConsumer();
        $this->_accessToken = $consumer->getAccessToken($_GET, $_SESSION['twitter_request_token']);
    }

    public function getLoginUrl()
    {
        $consumer = $this->_getConsumer();
        $_SESSION['twitter_request_token'] = $consumer->getRequestToken();
        return $consumer->getRedirectUrl();
    }

    public function _getUser()
    {
        $token = $this->_getAccessToken();
        return $this->request('https://api.twitter.com/1/users/show.json?id=' . $token->getParam('user_id'));
    }

    public function _getAccessToken()
    {
        if (empty($this->_accessToken)) {
            throw new Exception('No Access Token defined.');
        }
        return $this->_accessToken;
    }

    public function getIdentity()
    {
        $tUser = $this->_getUser();
        $identity = array(
            'guid' => 'twitter:' . $tUser['id'],
            'url' => 'http://twitter.com/' . $tUser['screen_name'],
            'username' => $tUser['screen_name'],
            'fullname' => $tUser['name'],
            'location' => $tUser['location'],
            'avatar_url' => $tUser['profile_image_url'],
        );
        return $identity;
    }

    public function setToken($token)
    {
        $accessToken = new Zend_Oauth_Token_Access();
        $accessToken->setToken($token['token']);
        $accessToken->setTokenSecret($token['token_secret']);
        $accessToken->setParam('user_id', $token['user_id']);
        $accessToken->setParam('screen_name', $token['screen_name']);

        $this->_accessToken = $accessToken;
    }

    public function getToken()
    {
        $accessToken = $this->_getAccessToken();
        return array(
            'token' => $accessToken->getToken(),
            'token_secret' => $accessToken->getTokenSecret(),
            'user_id' => $accessToken->getParam('user_id'),
            'screen_name' => $accessToken->getParam('screen_name'),
         );
    }

    public function _getCacheKey($url,  $params = array())
    {
        $token = $this->getToken();
        return get_class($this) . md5($url . serialize($params) . $token['token']);
    }

}
