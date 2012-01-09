<?php

class Ld_Services_Foursquare extends Ld_Services_Oauth2
{

    protected $_serviceName = 'foursquare';

    protected $_authorizeUrl = 'https://foursquare.com/oauth2/authenticate';

    protected $_accessTokenUrl = 'https://foursquare.com/oauth2/access_token';

    protected $_scope = null;

    protected $_tokenName = 'OAuth';

    public function _getUser()
    {
        $result = $this->request('https://api.foursquare.com/v2/users/self?v=20120107');
        return $result['response']['user'];
    }

    public function getIdentity()
    {
        $fUser = $this->_getUser();
        return $this->normaliseUser($fUser);
    }

    public function normaliseUser($fUser = array())
    {
        $user = array(
            'id' => $fUser['id'],
            'guid' => 'foursquare:' . $fUser['id'],
            'url' => 'https://foursquare.com/user/' . $fUser['id'],
            'username' => null,
            'fullname' => $fUser['firstName'] . ' ' .  (isset($fUser['lastName']) ? $fUser['lastName'] : ''),
            'avatar_url' => $fUser['photo'],
            'location' => $fUser['homeCity'],
            'gender' => $fUser['gender'],
        );
        return $user;
    }

}
