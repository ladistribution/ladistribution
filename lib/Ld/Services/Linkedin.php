<?php

class Ld_Services_Linkedin extends Ld_Services_Oauth1
{

    protected $_serviceName = 'linkedin';

    protected $_authorizeUrl = 'https://api.linkedin.com/uas/oauth/authenticate';

    protected $_requestTokenUrl = 'https://api.linkedin.com/uas/oauth/requestToken';

    protected $_accessTokenUrl = 'https://api.linkedin.com/uas/oauth/accessToken';

    public function getFields()
    {
        $fields = array(
            'id', 'first-name','last-name',
            'location',
            'picture-url',
            'date-of-birth',
            'twitter-accounts',
            'im-accounts',
            // 'phone-numbers',
            'public-profile-url'
        );
        return $fields;
    }

    public function _getUser()
    {
        $fields = implode(',' , $this->getFields());
        return $this->request('https://api.linkedin.com/v1/people/~:(' . $fields .')');
    }

    public function getIdentity()
    {
        $lUser = $this->_getUser();
        return $this->_normaliseUser($lUser);
    }

    public function _normaliseUser($lUser = array())
    {
        $user = array(
            'id' => $lUser['id'],
            'guid' => 'linkedin:' . $lUser['id'],
            'fullname' => $lUser['firstName'] . ' ' . $lUser['lastName']
        );
        if (isset($lUser['publicProfileUrl'])) {
            $user['url'] = $lUser['publicProfileUrl'];
            $x = explode('/', $lUser['publicProfileUrl']);
            // better check that ...
            $user['username'] = array_pop($x);
        }
        if (isset($lUser['pictureUrl'])) {
            $user['avatar_url'] = $lUser['pictureUrl'];
        } else {
            $user['avatar_url'] = 'http://static02.linkedin.com/scds/common/u/img/icon/icon_no_photo_40x40.png';
        }
        if (isset($lUser['location'])) {
            $user['location'] = $lUser['location']['name'];
        }
        if (isset($lUser['imAccounts']) && isset($lUser['imAccounts']['values'])) {
            foreach ($lUser['imAccounts']['values'] as $value) {
                if ($value['imAccountType'] == 'gtalk') {
                    $value['email'] = $value['imAccountName'];
                    $value['_gtalk'] = $value['imAccountName'];
                }
                if ($value['imAccountType'] == 'skype') {
                    $value['_skype'] = $value['imAccountName'];
                }
            }
        }
        return $user;
    }

    public function _getHttpClient()
    {
        $httpClient = parent::_getHttpClient();
        $httpClient->setHeaders('x-li-format', 'json');
        return $httpClient;
    }

}
