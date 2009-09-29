<?php

require_once 'BaseController.php';

/**
 * Users controller
 */
class Slotter_UsersController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        switch ($this->_getParam('action')) {
            case 'edit':
            case 'add-identity':
            case 'remove-identity':
                if (empty($this->currentUser) || $this->_getParam('id') != $this->currentUser['username']) {
                    if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
                        $this->_disallow();
                    }
                }
                break;
            case 'index':
                // allow
                break;
            default:
                if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
                    $this->_disallow();
                }
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $translator = $this->getTranslator();

        $usersPage = $this->_container->findOneByLabel( $translator->translate('Users') );

        $usersPage->addPage(array(
            'label' => 'New', 'module'=> 'slotter', 'controller' => 'users', 'action' => 'new'
        ));
        if ($this->_hasParam('id')) {
            $action = $this->getRequest()->action;
            $usersPage->addPage(array(
                'label' => ucfirst($action),
                'module'=> 'slotter',
                'route' => 'default',
                'controller' => 'users',
                'action' => $action,
                'params' => array('id' => $this->_getParam('id'))
            ));
        }
    }

    public function indexAction()
    {
        $applications = $this->site->getInstances('application');

        // if ($this->getRequest()->isPost()) {
        //     $roles = $this->_getParam('roles');
        //     foreach ($applications as $id => $application) {
        //         $path = $application['path'];
        //         if (isset($roles[$path])) {
        //             $instance = $this->site->getInstance($path);
        //             $instance->setUserRoles($roles[$path]);
        //         }
        //     }
        // }

        $this->view->users = $this->site->getUsers();

        // foreach ($applications as $id => $application) {
        //     $instance = $this->site->getInstance($application['path']);
        //     $applications[$id]['roles'] = $instance->getRoles();
        //     $applications[$id]['userRoles'] = $instance->getUserRoles();
        // }
        // 
        // $this->view->applications = $applications;
    }

    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $user = array(
                'username'   => $this->_getParam('username'),
                'password'   => $this->_getParam('password'),
                'fullname'   => $this->_getParam('fullname'),
                'email'      => $this->_getParam('email')
            );
            $this->site->addUser($user);

            // if it's the first user, make him administrator.
            $users = $this->site->getUsers();
            if (count($users) == 1 && Zend_Registry::isRegistered('instance')) {
                $instance = Zend_Registry::get('instance');
                $instance->setUserRoles(array($user['username'] => 'admin'));
                Ld_Auth::authenticate($user['username'], $user['password']);
            }

            $this->_redirector->gotoSimple('index', 'users');
        }
    }

    public function deleteAction()
    {
        $this->view->username = $id = $this->_getParam('id');
        if ($this->getRequest()->isPost()) {
            $this->site->deleteUser($id);
            $this->_redirector->gotoSimple('index', 'users');
        }
    }

    public function editAction()
    {
        $id = $this->_getParam('id');
        $this->view->user = $user = $this->site->getUser($id);
        if ($this->getRequest()->isPost()) {
            $params = array(
                'fullname'   => $this->_getParam('fullname'),
                'email'      => $this->_getParam('email')
            );
            $new_password = $this->_getParam('new_password');
            $new_password_again = $this->_getParam('new_password_again');
            if (!empty($new_password) && $new_password === $new_password_again) {
                $params['password'] = $new_password;
            }
            $this->site->updateUser($id, $params);
        }
    }

    public function addIdentityAction()
    {
        $root = 'http://' . $this->getRequest()->getServer('SERVER_NAME') . $this->getRequest()->getBaseUrl();

        if ($this->_hasParam('openid_identifier')) {

            $consumer = new Zend_OpenId_Consumer();
            if (!$consumer->login($this->_getParam('openid_identifier'), null, $root)) {
                throw new Exception("OpenID login failed.");
            }

        } elseif ($this->_hasParam('openid_mode')) {

            if ($this->_getParam('openid_mode') == 'id_res') {
                $consumer = new Zend_OpenId_Consumer();
                if ($consumer->verify($_GET)) {
                    $userId = $this->_getParam('id');
                    $this->_addUserIdentity($userId, $this->_getOpenidIdentity());
                    $user = $this->site->getUser($id);
                    $this->_redirector->gotoSimple('edit', 'users', 'slotter', array('id' => $userId));
                } else {
                    throw new Exception("OpenID Authentication failed: " . $consumer->getError());
                }
            } else if ($this->_getParam('openid_mode') == 'id_res') {
                throw new Exception("OpenID Authentication canceled.");
            }

        }

    }

    protected function _addUserIdentity($user, $identity)
    {
        if (is_string($user)) {
            $user = $this->site->getUser($user);
        }

        $test = $this->site->getUserByUrl($identity);
        if (isset($test)) {
            throw new Exception("This OpenID is already used.");
        }

        if (empty($user['identities'])) {
            $user['identities'] = array();
        }

        $user['identities'][] = $identity;

        $this->site->updateUser($user['username'], $user);
    }

    protected function _getOpenidIdentity()
    {
        $params = $this->_getAllParams();

        if (isset($_SESSION["zend_openid"]['claimed_id'])) {
            return $_SESSION["zend_openid"]['claimed_id'];
        } else if (isset($params["openid_claimed_id"])) {
            return $params["openid_claimed_id"];
        } else if (isset($params["openid1_claimed_id"])) {
            return $params["openid_claimed_id"];
        } else if (isset($params["openid_identity"])){
            return $params["openid_identity"];
        }
    }

    public function removeIdentityAction()
    {
        $id = $this->_getParam('id');
        $identity = urldecode( $this->_getParam('identity') );

        $user = $this->site->getUser($id);

        foreach ($user['identities'] as $key => $userIdentity) {
            if ($userIdentity == $identity) {
                unset($user['identities'][$key]);
                break;
            }
        }

        $this->site->updateUser($id, $user);

        $this->_redirector->gotoSimple('edit', 'users', 'slotter', array('id' => $id));
    }

}
