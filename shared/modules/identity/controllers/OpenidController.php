<?php

/**
 * Index controller.
 */
class Identity_OpenidController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->appendTitle( $this->getTranslator()->translate('Identity') );

        $authUrl = $this->view->url(
            array('module' => 'identity', 'controller' => 'openid', 'action' => 'auth'), 'default', false);

        $storage = new Zend_OpenId_Provider_Storage_File(LD_TMP_DIR . '/openid');
        $this->_server = new Zend_OpenId_Provider($authUrl, null, null, $storage);

        $this->view->serverUrl = Zend_OpenId::absoluteURL($authUrl);

        if (isset($this->user)) {
            $this->username = $this->user['username'];
        }
    }

    public function _restrict()
    {
        if ($this->authenticated == false) {
            $this->_forward('login', 'auth', 'default');
            return true;
        }
    }

    public function profileAction()
    {
        $this->view->identity = Zend_OpenId::absoluteURL($this->view->url(
            array('module' => 'identity', 'controller' => 'openid', 'id' => $this->_getParam('id')), 'identity'));
    }

    public function authAction()
    {
        $mode = $this->_getParam('openid_mode');

        switch ($mode) {
            case 'checkid_setup':
                if ($this->_restrict()) {
                    return;
                }
                $userIdentity = $this->getSite()->getBaseUrl() . 'identity/' . $this->username;
                if (!$this->_server->hasUser($userIdentity)) {
                    // we don't care about the password
                    $this->_server->register($userIdentity, $this->username); 
                }
            case 'trust':
                $params = $this->_getAllParams();

                $siteRoot = $this->_server->getSiteRoot($params);
                $trustedSites = (array)$this->_server->getTrustedSites();

                $result = $this->_server->login($this->_getParam('openid_identity'), $this->username);
                if ($result) {
                    // we know the claimed identity, the request is valid, we can move forward
                    if (isset($trustedSites) && in_array($siteRoot, $trustedSites)) {
                        // if the user is already trusting this site, we can directly redirect
                        $this->_server->respondToConsumer($params);
                    }
                    
                    $this->view->openid_identity = $this->_getParam('openid_identity');
                } else {
                    // else we use the default identity of the connected user
                    $this->_server->login($userIdentity, $this->username);
                    $this->view->openid_identity = $userIdentity;
                    
                    // is this ok with common openid process ?
                    $this->_setParam('openid_identity', $userIdentity);
                    $this->_setParam('openid_claimed_id', $userIdentity);
                }
                
                $this->view->params = $this->_getAllParams();

                if ($this->getRequest()->isPost()) {
                    if (isset($_POST['allow'])) {
                        if (isset($_POST['forever'])) {
                            $this->_server->allowSite($siteRoot);
                        }
                        $this->_server->respondToConsumer($params);
                    } else if (isset($_POST['deny'])) {
                        Zend_OpenId::redirect($this->_getParam('openid_return_to'), array('openid.mode'=>'cancel'));
                    }
                }
                $this->view->mode = 'trust';
                $this->view->openidServer = $this->_server;
                $this->view->siteRoot = $this->_server->getSiteRoot($params);
                break;
            default:
                $ret = $this->_server->handle();
                if ($ret) {
                    echo $ret;
                    exit;
                }
        }
    }

}
