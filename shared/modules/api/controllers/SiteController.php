<?php

class Api_SiteController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        if ($this->isInitialised()) {
            // -> Check API Key
        }

        $this->noRender();

        $this->getResponse()->setHeader('Content-Type', 'application/json');
    }

    public function isInitialised()
    {
        $users = $this->admin->getUsers();
        $roles = $this->admin->getUserRoles();

        return empty($users) && empty($roles) ? false : true;
    }

    public function ressourceNotAvailable()
    {
        $this->getResponse()->appendBody('Ressource not available.');
    }

    public function methodNotAvailable()
    {
        $this->getResponse()->appendBody('Method not available.');
    }

    public function indexAction()
    {
        return $this->ressourceNotAvailable();
    }

    public function initAction()
    {
        if ($this->isInitialised()) {
            return $this->ressourceNotAvailable();
        }

        if (!$this->getRequest()->isPost()) {
            return $this->methodNotAvailable();
        }

        $params = Zend_Json::decode( $this->getRequest()->getRawBody() );

        $user = array(
            'origin'           => 'Api:init',
            'username'         => $params['username'],
            'fullname'         => $params['fullname'],
            'email'            => $params['email'],
            'hash'             => $params['hash'],
            'identities'       => array($params['identity'])
        );

        $user = $this->getSite()->addUser($user);

        // create API Key
        $api_key = Ld_Auth::generatePhrase();
        $this->getSite()->setConfig('api_key', $api_key);

        // Response
        $this->getResponse()->setBody(Zend_Json::encode(array('api_key' => $api_key)));
    }

}
