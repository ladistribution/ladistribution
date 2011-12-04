<?php

require_once 'facebook-php-sdk/facebook.php';

class Ld_Services_Facebook extends Ld_Services_Base
{

    protected $_serviceName = 'facebook';

    protected $_consumer = null;

    public function _getConsumer()
    {
        if (empty($this->_consumer)) {
            $this->_consumer = new Facebook(array(
              'appId'  => $this->getClientId(),
              'secret' => $this->getClientSecret()
            ));
            // Automatic Refresh Callback
            if (isset($_GET['code']) && !Ld_Auth::isAnonymous() && $user = Ld_Auth::getUser()) {
                $user['identities']['facebook']['oauth_access_token'] = $this->getToken();
                $this->getSite()->updateUser($user);
            }
        }
        return $this->_consumer;
    }

    public function _getScope()
    {
        // https://developers.facebook.com/docs/reference/api/permissions/
        return array(
            'user_about_me', 'friends_about_me',
            'user_location', 'friends_location',
            'user_hometown', 'friends_hometown',
            'user_website', 'friends_website',
            'read_friendlists',
            // 'offline_access',
            // 'email',
        );
    }

    public function authorize()
    {
        $callbackUrl = $this->getCallbackUrl();
        $loginUrl = $this->getLoginUrl($callbackUrl);
        header('Location:' . $loginUrl);
        exit;
    }

    public function callback()
    {
        $facebook = $this->_getConsumer();
        $me = $facebook->api('/me');
    }

    public function test()
    {
        try {
            $facebook = $this->_getConsumer();
            $me = $facebook->api('/me');
            return empty($me) ? false : true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function _getUser()
    {
        return $this->request('/me');
    }

    public function setToken($token)
    {
        $facebook = $this->_getConsumer();
        $facebook->setAccessToken($token['accessToken']);
    }

    public function getToken()
    {
        $facebook = $this->_getConsumer();
        return array(
            'accessToken' => $facebook->getAccessToken()
        );
    }

    public function getLoginUrl($redirect_uri = null)
    {
        // if (empty($redirect_uri)) {
        //     $redirect_uri = $this->getCallbackUrl();
        // }
        $facebook = $this->_getConsumer();
        $params = array();
        $params['scope'] = $this->_getScope();
        if ($redirect_uri) {
            $params['redirect_uri'] = $redirect_uri;
        }
        return $facebook->getLoginUrl($params);
    }

    public function getIdentity()
    {
        $fbUser = $this->_getUser();
        $user = array(
            'guid' => 'facebook:' . $fbUser['id'],
            'url' => $fbUser['link'],
            'fullname' => $fbUser['name'],
        );
        if (isset($fbUser['username'])) {
            $user['username'] = $fbUser['username'];
        }
        if (isset($fbUser['gender'])) {
            $user['gender'] = $fbUser['gender'];
        }
        if (isset($fbUser['email'])) {
            $user['email'] = $fbUser['email'];
        }
        if (isset($fbUser['location'])) {
            $user['location'] = $fbUser['location']['name'];
        }
        $user['alias_url'] = array(
            'http://www.facebook.com/' . $fbUser['id']
        );
        $user['avatar_url'] = 'https://graph.facebook.com/' . $fbUser['id'] . '/picture?type=square';
        return $user;
    }

    public function _makeRequest($query)
    {
        $facebook = $this->_getConsumer();
        try {
            $result = $facebook->api($query);
        } catch (Exception $e) { // FacebookApiException
            $params = array(
              'scope' => $this->_getScope(),
            );
            header('Location:' . $facebook->getLoginUrl($params) );
            exit;
        }
        return $result;
    }

}
