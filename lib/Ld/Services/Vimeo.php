<?php

class Ld_Services_Vimeo extends Ld_Services_Oauth1
{

    protected $_serviceName = 'vimeo';

    protected $_authorizeUrl = 'http://vimeo.com/oauth/authorize';

    protected $_requestTokenUrl = 'http://vimeo.com/oauth/request_token';

    protected $_accessTokenUrl = 'http://vimeo.com/oauth/access_token';

    public function _getUser()
    {
        $result = $this->request('http://vimeo.com/api/rest/v2', 'GET', array('method' => 'vimeo.people.getInfo'));
        return $result['person'];
    }

    public function getIdentity()
    {
        $vUser = $this->_getUser();
        return $this->normaliseUser($vUser);
    }

    public function normaliseUser($vUser = array())
    {
        $user = array();
        $user['id'] = $id = $vUser['id'];
        $user['guid'] = 'vimeo:' . $id;
        $user['url'] = $vUser['profileurl'];
        $user['username'] = $vUser['username'];
        $user['fullname'] = $vUser['display_name'];
        $user['avatar_url'] = $vUser['portraits']['portrait'][0]['_content'];
        if (isset($vUser['location'])) {
            $user['location'] = $vUser['location'];
        }
        if (isset($vUser['created_at'])) {
            $user['created_on'] = $vUser['created_at'];
        }
        return $user;
    }

    public function _getHttpClient()
    {
        $httpClient = parent::_getHttpClient();
        $httpClient->setParameterGet('format', 'json');
        return $httpClient;
    }

    public function authorize()
    {
        $consumer = $this->_getConsumer();
        $token = $consumer->getRequestToken();

        $session = $this->getSession();
        $session->token = serialize($token);

        $redirectUrl = $consumer->getRedirectUrl();
        $redirectUrl .= '&permission=write';
        header('Location: ' . $redirectUrl);
        exit(1);
    }

}
