<?php

class Api_OauthController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->_oauthStorage = new Ld_OAuth2_Storage();

        $config = array(
            OAuth2::CONFIG_SUPPORTED_SCOPES => array('openid', 'profile', 'email')
        );

        require_once "oauth2-php/OAuth2.php";
        $this->oauth = new OAuth2($this->_oauthStorage, $config);
    }

    public function registerAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_getParam('type') == 'client_associate') {

                // Register new client
                $result = $this->_oauthStorage->addClient(array(
                    'application_name' => $this->_getParam('application_name'),
                    'application_url' => $this->_getParam('application_url'),
                    'application_type' => $this->_getParam('application_type'),
                    'redirect_uri' => $this->_getParam('redirect_uri')
                ));

                $this->noRender();
                $this->getResponse()->setHeader('Cache-Control', 'no-store');
                $this->getResponse()->setBody(Zend_Json::encode($result));

            } elseif ($this->_getParam('type') == 'client_update') {

                // Update new client
                $client_id = $this->_getParam('client_id');
                $client_secret = $this->_getParam('client_secret');

                // Not implemented

            }
        }
    }

    public function authorizeAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        // Client
        $clientId = $this->_getParam('client_id');
        $this->view->client = $client = $this->site->getModel('clients')->get($clientId);
        if (empty($client)) {
            throw new Exception("Unknown client.");
        }

        // Already authorised ?
        $accessTokens = $this->site->getModel('access_tokens')->searchByKey('user_id', $user['id']);
        foreach ($accessTokens as $token) {
            if ($token['client_id'] == $clientId) {
                return $this->oauth->finishClientAuthorization(true, $user['id'], $_GET);
            }
        }

        $this->view->okLabel = $okLabel = $this->translate('Sign In');
        $this->view->cancelLabel = $cancelLabel = $this->translate('Cancel');

        if ($this->getRequest()->isPost()) {
            $this->oauth->finishClientAuthorization($_POST["accept"] == $okLabel, $user['id'], $_POST);
        }

        try {
            $this->view->authParams = $this->oauth->getAuthorizeParams();
        } catch (OAuth2ServerException $oauthError) {
            $oauthError->sendHttpResponse();
        }

        $this->appendTitle( $this->translate('Identity') );
        $this->view->layoutTitle = $this->translate('Identity');

        // Prevent Click-Jacking
        $this->getResponse()->setHeader('X-Frame-Options', 'DENY');
    }

    public function tokenAction()
    {
        $this->noRender();

        try {
            $this->oauth->grantAccessToken(null, array());
        }
        catch (OAuth2ServerException $oauthError) {
            $oauthError->sendHttpResponse();
        }
    }

    public function userinfoAction()
    {
        $this->_forward('me', 'identity');
    }

}