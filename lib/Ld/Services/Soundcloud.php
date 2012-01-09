<?php

class Ld_Services_Soundcloud extends Ld_Services_Oauth2
{

    protected $_serviceName = 'soundcloud';

    protected $_authorizeUrl = 'https://soundcloud.com/connect';

    protected $_accessTokenUrl = 'https://api.soundcloud.com/oauth2/token';

    protected $_scope = 'non-expiring';

    protected $_tokenName = 'OAuth';

    public function _getUser()
    {
        return $this->request('https://api.soundcloud.com/me.json');
    }

    public function getIdentity()
    {
        $sUser = $this->_getUser();
        return $this->normaliseUser($sUser);
    }

    public function normaliseUser($sUser = array())
    {
        $user = array(
            'id' => $sUser['id'],
            'guid' => 'soundcloud:' . $sUser['id'],
            'url' => $sUser['permalink_url'],
            'username' => isset($sUser['permalink']) ? $sUser['permalink'] : $sUser['username'],
            'fullname' => isset($sUser['full_name']) ? $sUser['full_name'] : $sUser['username'],
            'avatar_url' => $sUser['avatar_url'],
            'location' => $sUser['city'] . ', ' . $sUser['country'],
        );
        return $user;
    }

}
