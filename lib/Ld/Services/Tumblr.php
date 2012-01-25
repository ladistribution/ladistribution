<?php

class Ld_Services_Tumblr extends Ld_Services_Oauth1
{

    protected $_serviceName = 'tumblr';

    protected $_authorizeUrl = 'http://www.tumblr.com/oauth/authorize';

    protected $_requestTokenUrl = 'http://www.tumblr.com/oauth/request_token';

    protected $_accessTokenUrl = 'http://www.tumblr.com/oauth/access_token';

    public function _getUser()
    {
        $result = $this->request('http://api.tumblr.com/v2/user/info', 'POST');
        return $result['response']['user'];
    }

    public function getIdentity()
    {
        $tUser = $this->_getUser();
        $user['id'] = $tUser['name'];
        $user['guid'] = 'tumblr:' . $tUser['name'];
        $user['username'] = $tUser['name'];
        foreach ($tUser['blogs'] as $blog) {
            if (empty($user['url']) || $user['username'] == $blog['name'] || $blog['primary'] == true) {
                $user['url'] = $blog['url'];
                $pu = parse_url($user['url']);
                $user['avatar_url'] = 'http://api.tumblr.com/v2/blog/' . $pu['host'] . '/avatar';
            }
        }
        return $user;
    }

}
