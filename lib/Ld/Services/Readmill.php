<?php

class Ld_Services_Readmill extends Ld_Services_Oauth2
{

    protected $_serviceName = 'readmill';

    protected $_authorizeUrl = 'http://readmill.com/oauth/authorize';

    protected $_accessTokenUrl = 'http://readmill.com/oauth/token';

    protected $_scope = 'non-expiring';

    protected $_tokenName = 'OAuth';

    public function _getUser()
    {
        return $this->request('http://api.readmill.com/me');
    }

    public function getIdentity()
    {
        $sUser = $this->_getUser();
        return $this->normaliseUser($sUser);
    }

    public function normaliseUser($rUser = array())
    {
        $user = array(
            'guid' => 'readmill:' . $rUser['id'],
            'url' => $rUser['permalink_url'],
            'username' => $rUser['username'],
            'fullname' => $rUser['fullname'],
            'avatar_url' => $rUser['avatar_url'],
            'location' => $rUser['city'] . ', ' . $rUser['country'],
        );
        return $user;
    }

}
