<?php

class Ld_Services_Flickr extends Ld_Services_Oauth1
{

    protected $_serviceName = 'flickr';

    protected $_authorizeUrl = 'http://www.flickr.com/services/oauth/authorize';

    protected $_requestTokenUrl = 'http://www.flickr.com/services/oauth/request_token';

    protected $_accessTokenUrl = 'http://www.flickr.com/services/oauth/access_token';

    public function _getUser()
    {
        $result = $this->request('http://api.flickr.com/services/rest', 'GET', array('method' => 'flickr.test.login'));
        $user_id = $result['user']['id'];
        return $this->request('http://api.flickr.com/services/rest', 'GET', array('method' => 'flickr.people.getInfo', 'user_id' => $user_id));
    }

    public function getIdentity()
    {
        $fUser = $this->_getUser();
        return $this->_normaliseUser($fUser);
    }

    public function _normaliseUser($fUser = array())
    {
        $user = array();
        $user['id'] = $id = $fUser['person']['id'];
        $user['guid'] = 'flickr:' . $id;
        $user['url'] = $fUser['person']['profileurl']['_content'];
        $user['username'] = $fUser['person']['username'];
        $user['fullname'] = $fUser['person']['realname']['_content'];
        $user['location'] = $fUser['person']['location']['_content'];
        $user['avatar_url'] = "http://www.flickr.com/buddyicons/$id.jpg";
        $user['created_at'] = $fUser['person']['photos']['firstdatetaken']['_content'];
        return $user;
    }

    public function _getHttpClient()
    {
        $httpClient = parent::_getHttpClient();
        $httpClient->setParameterGet('format', 'json');
        $httpClient->setParameterGet('nojsoncallback', '1');
        return $httpClient;
    }

}
