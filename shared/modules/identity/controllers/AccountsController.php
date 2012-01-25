<?php

require_once 'BaseController.php';

class Identity_AccountsController extends Identity_BaseController
{

    public function indexAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        $this->view->user = $this->targetUser;

        $this->view->identities = isset($this->targetUser['identities']) ? $this->targetUser['identities'] : array();

        $this->view->services = $services = $this->_getServices();

        $this->view->otherIdentities = array();
        foreach ($this->view->identities as $id => $identity) {
            if (!isset($services[$id])) {
                $this->view->otherIdentities[$id] = $identity;
            }
        }
    }

    public function connectAction()
    {
        if (!Ld_Auth::isAuthenticated()) {
            return $this->disallow();
        }

        $service = $this->_getParam('service');

        $user = Ld_Auth::getUser();

        // store referer, if passed
        $session = new Zend_Session_Namespace("Ld_Identity_Connect");
        if ($this->_hasParam('ref')) {
            $referer = base64_decode($this->_getParam('ref'));
            $pu = parse_url($referer);
            if ($pu['host'] == $this->getSite()->getHost() || $pu['host'] == $_SERVER['HTTP_HOST']) {
                $session->referer = $referer;
            }
        } else {
            unset($session->referer);
        }

        $consumer = $this->_getService($service);
        $consumer->authorize();
        exit;
    }

    public function disconnectAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        if ($this->getRequest()->isPost()) {
            $id = $this->_getParam('service');
            if (isset($user['identities'][$id])) {
                unset($user['identities'][$id]);
                $this->site->updateUser($user);
            }
        }

        return $this->_redirectToIndex();
    }

    public function detailsAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        $service = $this->_getParam('service');
        $consumer = $this->_getService($service);

        if (isset($user['identities'][$service])) {
            $identity = $user['identities'][$service];
            $consumer->setToken($identity['oauth_access_token']);
            $raw = $consumer->_getUser();
            $processed = $consumer->getIdentity();
            $this->noRender();
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setBody(Zend_Json::encode(compact('processed', 'raw')));
        }
    }

    public function callbackAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        $service = $this->_getParam('service');

        $consumer = $this->_getService($service);
        $consumer->callback();

        if (isset($user['identities'][$service])) {
            unset($user['identities'][$service]);
            $this->site->updateUser($user);
        }

        $identity = $consumer->getIdentity();
        $identity['oauth_access_token'] = $consumer->getToken();
        $identity['confirmed'] = true;
        $this->_addIdentity($identity, $service);

        $session = new Zend_Session_Namespace("Ld_Identity_Connect");
        if (isset($session->referer)) {
            $redirectUrl = $session->referer;
            unset($session->referer);
        } else {
            $redirectUrl = $this->view->url(array('module' => 'identity', 'controller' => 'accounts'), 'identity-accounts', false);
        }

        $this->redirectTo($redirectUrl);
    }

    public function addAction()
    {
        if (!$user = Ld_Auth::getUser()) {
            return $this->disallow();
        }

        $connect = new Ld_Auth_Adapter_Connect();
        $redirectUrl = $this->admin->buildAbsoluteUrl(array('module' => 'identity', 'controller' => 'accounts', 'action' => 'add'));
        $connect->setRedirectUrl($redirectUrl);

        // Callback
        if ($this->_hasParam('state') && $this->_hasParam('code')) {
            $result = $connect->getAuthResult();
            if ($result->isValid()) {
                $identity = $connect->getUserinfo();
                try {
                    $this->_addIdentity($identity);
                    $this->_flashMessenger->addMessage( $this->translate("Identity added.") );
                } catch (Exception $e) {
                    $this->_flashMessenger->addMessage( $e->getMessage() );
                }
            }

        }

        // Connect
        if ($this->_hasParam('openid_identifier')) {
            $url = $this->_getParam('openid_identifier');
            if (Zend_Uri_Http::check($url)) {
                $connect->setIdentityUrl($url);
                if ($connect->isConnect()) { // check that the identity server support Instant Connect
                    return $connect->redirect();
                }
            }
        }

        return $this->_redirectToIndex();
    }

    public function removeAction()
    {
       if (!$user = Ld_Auth::getUser()) {
           return $this->disallow();
       }

       if ($this->getRequest()->isPost()) {
           $id = $this->_getParam('id');
           if (isset($user['identities'][$id])) {
               unset($user['identities'][$id]);
               $this->site->updateUser($user);
           }
       }

       return $this->_redirectToIndex();
    }

    protected function _addIdentity($identity, $service = null)
    {
        $user = $this->currentUser;

        if (isset($identity['url'])) {
            $identity['url'] = $this->_normaliseUrl($identity['url']);
            $test = $this->site->getModel('users')->getUserByUrl($identity['url']);
            if (isset($test)) {
                if ($test['username'] == $user['username']) {
                    throw new Exception("You already claimed this identity.");
                } else {
                    throw new Exception("This identity is already claimed.");
                }
            }
        }

        if (empty($user['identities'])) {
            $user['identities'] = array();
        }

        $id = isset($service) ? $service : Ld_Utils::getUniqId();
        $user['identities'][$id] = $identity;

        $this->site->updateUser($user['username'], $user);
    }

    protected function _normaliseUrl($url)
    {
        $url = str_ireplace('https://', 'http://', $url);
        return $url;
    }

    protected function _redirectToIndex()
    {
        $url = $this->view->url(array('module' => 'identity', 'controller' => 'accounts', 'action' => 'index'), 'default', false);
        return $this->redirectTo($url);
    }

}
