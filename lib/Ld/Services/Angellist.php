<?php

class Ld_Services_Angellist extends Ld_Services_Oauth2
{

    protected $_serviceName = 'angellist';

    protected $_authorizeUrl = 'https://angel.co/api/oauth/authorize';

    protected $_accessTokenUrl = 'https://angel.co/api/oauth/token';

    public function _getUser()
    {
        return $this->request('https://api.angel.co/1/me');
    }

    public function getIdentity()
    {
        $aUser = $this->_getUser();
        return $this->normaliseUser($aUser);
    }

    public function normaliseUser($aUser = array())
    {
        $url = $aUser['angellist_url'];
        $xurl = explode('/', $url);
        $username = array_pop($xurl);
        $user = array(
            'id' => $aUser['id'],
            'guid' => 'angellist:' . $aUser['id'],
            'url' => $url,
            'username' => $username,
            'fullname' => $aUser['name']
        );
        if (isset($aUser['image'])) {
            $user['avatar_url'] = $aUser['image'];
        }
        if (isset($aUser['thumb_url'])) {
            $user['avatar_url'] = $aUser['thumb_url'];
        }
        return $user;
    }

}
