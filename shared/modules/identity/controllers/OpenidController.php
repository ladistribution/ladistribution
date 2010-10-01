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
        $this->view->identityUrl = $this->admin->getIdentityUrl( $this->_getParam('id') );
    }

    public function authAction()
    {
        $mode = $this->_getParam('openid_mode');

        switch ($mode) {
            case 'checkid_setup':
                if ($this->_restrict()) {
                    return;
                }
                $userIdentityUrl = $this->admin->getIdentityUrl($this->username);
                $this->_server = $this->admin->getOpenidProvider($this->username, false);

                // no break, we continue after
            case 'trust':
                $params = $this->view->params = $this->_getAllParams();

                $siteRoot = $this->view->siteRoot = $this->_server->getSiteRoot($params);

                $result = $this->_server->login($this->_getParam('openid_identity'), $this->username);
                if ($result) {
                    // we get trusted sites
                    $trustedSites = $this->_server->getTrustedSites();
                    // we know the claimed identity, the request is valid, we can move forward
                    if (is_array($trustedSites) && isset($trustedSites[$siteRoot]) && $trustedSites[$siteRoot] === true) {
                        // if the user is already trusting this site, we can directly redirect
                        $this->_server->respondToConsumer($params);
                    }

                    $this->view->openid_identity = $this->_getParam('openid_identity');
                } else {
                    // else we use the default identity of the connected user
                    $this->_server->login($userIdentityUrl, $this->username);
                    $this->view->openid_identity = $userIdentityUrl;

                    // is this ok with common openid process ?
                    $this->_setParam('openid_identity', $userIdentityUrl);
                    $this->_setParam('openid_claimed_id', $userIdentityUrl);
                }

                if ($this->getRequest()->isPost()) {
                    if (isset($_POST['allow'])) {
                        if (isset($_POST['forever'])) {
                            $this->_server->allowSite($siteRoot);
                        }
                        $this->_server->respondToConsumer($params);
                    } else if (isset($_POST['deny'])) {
                        Zend_OpenId::redirect($this->_getParam('openid_return_to'), array('openid.mode' => 'cancel'));
                    }
                }
                $this->view->mode = 'trust';
                $this->view->identityUrl = $this->_server->getLoggedInUser();
                break;
            default:
                $this->_server = $this->admin->getOpenidProvider();
                $ret = $this->_server->handle();
                if ($ret) {
                    echo $ret;
                    exit;
                }
        }
    }

}
