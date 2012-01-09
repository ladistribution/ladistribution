<?php

class Ld_Services_Twitter extends Ld_Services_Oauth1
{

    protected $_serviceName = 'twitter';

    protected $_serviceHost = 'twitter.com';

    protected $_baseApiUrl = 'https://api.twitter.com/1';

    protected $_authorizeUrl = 'http://twitter.com/oauth/authenticate';

    protected $_requestTokenUrl = 'http://twitter.com/oauth/request_token';

    protected $_accessTokenUrl = 'http://twitter.com/oauth/access_token';

    public function getBaseApiUrl()
    {
        return $this->_baseApiUrl;
    }

    public function _getUser()
    {
        $token = $this->_getAccessToken();
        return $this->request( $this->_baseApiUrl . '/account/verify_credentials.json');
    }

    public function getIdentity()
    {
        $tUser = $this->_getUser();
        return $this->_normaliseUser($tUser);
    }

    public function _normaliseUser($tUser = array())
    {
        $user = array(
            'id' => $tUser['id'],
            'guid' => $this->_serviceName . ':' . $tUser['id'],
            'url' => 'http://' . $this->_serviceHost . '/' . $tUser['screen_name'],
            'username' => $tUser['screen_name'],
            'fullname' => trim($tUser['name']),
            'location' => $tUser['location'],
            'avatar_url' => isset($tUser['profile_image_url_https']) ? $tUser['profile_image_url_https'] : $tUser['profile_image_url']
        );
        return $user;
    }

}
