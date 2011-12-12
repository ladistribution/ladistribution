<?php

class Ld_Services_Github extends Ld_Services_Oauth2
{

    protected $_serviceName = 'github';

    protected $_authorizeUrl = 'https://github.com/login/oauth/authorize';

    protected $_accessTokenUrl = 'https://github.com/login/oauth/access_token';

    protected $_scope = 'user';

    public function _getUser()
    {
        return $this->request('https://api.github.com/user');
    }

    public function getIdentity()
    {
        $gUser = $this->_getUser();
        return $this->_normaliseUser($gUser);
    }

    public function _normaliseUser($gUser = array())
    {
        $user = array(
            'guid' => 'github:' . $gUser['id'],
            'url' => $gUser['html_url'],
            'username' => $gUser['login'],
            'fullname' => isset($gUser['name']) ? $gUser['name'] : $gUser['login'],
            'avatar_url' => $gUser['avatar_url'],
            'created_at' => $gUser['created_at'],
        );
        if (isset($gUser['location'])) {
            $user['location'] = $gUser['location'];
        }
        if (isset($gUser['email'])) {
            $user['email'] = $gUser['email'];
        }
        return $user;
    }

}
