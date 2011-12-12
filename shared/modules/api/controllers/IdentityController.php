<?php

class Api_IdentityController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->noRender();

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        $storage = new Ld_OAuth2_Storage();

        require_once "oauth2-php/OAuth2.php";
        $this->oauth = new OAuth2($storage);
    }

    public function meAction()
    {
        try {

          $token = $this->_getRequestToken();
          $verify = $this->oauth->verifyAccessToken($token);
          $user = $this->getSite()->getUser($verify['user_id']);
          $me = $this->_formatUser($user);
          $this->getResponse()->setBody(Zend_Json::encode($me));

        } catch (OAuth2ServerException $oauthError) {
          $oauthError->sendHttpResponse();
        }
    }

    protected static function _getRequestToken()
    {
        if (isset($_GET[OAuth2::TOKEN_PARAM_NAME])) {
            return $_GET[OAuth2::TOKEN_PARAM_NAME];
        }
        if (isset($_POST[OAuth2::TOKEN_PARAM_NAME])) {
            return $_POST[OAuth2::TOKEN_PARAM_NAME];
        }
    }

    protected function _formatUser($user)
    {
        $formatedUser = array(
            'username' => $user['username'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'url' => Ld_Ui::getIdentityUrl($user),
            'avatar_url' => Ld_Ui::getAvatarUrl($user),
            'url_alias' => array(
                $this->admin->buildAbsoluteUrl(array('id' => $user['id']), 'vanity'),
                $this->admin->buildAbsoluteUrl(array('id' => $user['username']), 'identity')
            )
        );
        return $formatedUser;
    }

}
