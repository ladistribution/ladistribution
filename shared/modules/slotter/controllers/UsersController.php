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
            case 'add':
            case 'index':
                // allow
                if (!$this->_acl->isAllowed($this->userRole, 'instances', 'manage')) {
                    $this->_disallow();
                }
                break;
            default:
                if (!$this->_acl->isAllowed($this->userRole, 'users', 'manage')) {
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

        $usersPage->addPage(array(
            'label' => 'Add', 'module'=> 'slotter', 'controller' => 'users', 'action' => 'add'
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
        if ($this->getRequest()->isPost()) {
            // save user order
            if ($this->_hasParam('userOrder')) {
                $userOrder = array_merge($this->admin->getUserOrder(), (array)$this->_getParam('userOrder'));
                $this->admin->setUserOrder($userOrder);
            }
            // save user roles
            if ($this->_hasParam('userRoles')) {
                $userRoles = array_merge($this->admin->getUserRoles(), (array)$this->_getParam('userRoles'));
                $this->admin->setUserRoles($userRoles);
            }
            // remove users
            if ($this->_hasParam('userAction')) {
                switch($this->_getParam('userAction')) {
                    case 'remove':
                        if ($this->_hasParam('users')) {
                            $userRoles = $this->admin->getUserRoles();
                            foreach ((array)$this->_getParam('users') as $username => $on) {
                                if (isset($userRoles[$username])) {
                                    unset($userRoles[$username]);
                                }
                            }
                            $this->admin->setUserRoles($userRoles);
                        }
                        break;
                    case 'delete':
                        if ($this->_hasParam('users')) {
                            $userRoles = $this->admin->getUserRoles();
                            foreach ((array)$this->_getParam('users') as $username => $on) {
                                $this->site->deleteUser($username);
                                if (isset($userRoles[$username])) {
                                    unset($userRoles[$username]);
                                }
                            }
                            $this->admin->setUserRoles($userRoles);
                        }
                        break;
                }
            }
            // redirect or render
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->noRender();
                $this->getResponse()->appendBody('ok');
            } else {
                $this->_redirector->gotoSimple('index', 'users');
            }
            return;
        }

        $this->view->users = $this->_getUsers();
        $this->view->roles = $this->admin->getRoles();
        $this->view->userRoles = $this->admin->getUserRoles();

        $this->view->canManageRoles = $this->_acl->isAllowed($this->userRole, 'instances', 'manage');
        $this->view->canManageUsers = $this->_acl->isAllowed($this->userRole, 'users', 'manage');
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
                // in this case, we believe the user wants to go back to the index
                $this->_redirector->gotoSimple('index', 'index');
                return;
            }

            $this->_redirector->gotoSimple('index', 'users');
        }
    }

    public function addAction()
    {
        // if (!$this->getSite()->hasParentSite()) {
        //     $this->_disallow();
        // }

        if ($this->_hasParam('users') || $this->_hasParam('query')) {
            $users = $this->view->users = $this->_getUsers();
        }

        if ($this->getRequest()->isPost() && $this->_hasParam('users')) {
            $userRoles = $this->admin->getUserRoles();
            foreach ((array)$this->_getParam('users') as $username => $on) {
                if (empty($userRoles[$username])) {
                    $userRoles[$username] = 'user'; // default role
                }
            }
            $this->admin->setUserRoles($userRoles);
            // redirect
            $this->_redirector->gotoSimple('index', 'users');
        }

        if ($this->_hasParam('query')) {
            $this->view->query = $query = $this->_getParam('query');
            if ($this->getSite()->isChild()) {
                $this->view->searchUsers = $this->getSite()->getParentSite()->getUsers(compact('query'));
            } else {
                $this->view->searchUsers = $this->getSite()->getUsers(compact('query'));
            }
        } else {
            $this->view->query = '';
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
        $this->view->user = $this->site->getUser($id);
    }

    public function addIdentityAction()
    {
        $root = 'http://' . $this->getRequest()->getServer('SERVER_NAME') . $this->getRequest()->getBaseUrl();

        $storage = new Zend_OpenId_Consumer_Storage_File(LD_TMP_DIR . '/openid');

        if ($this->_hasParam('openid_identifier')) {

            $consumer = new Zend_OpenId_Consumer($storage );
            if (!$consumer->login($this->_getParam('openid_identifier'), null, $root)) {
                throw new Exception("OpenID login failed: " . $consumer->getError());
            }

        } elseif ($this->_hasParam('openid_mode')) {

            if ($this->_getParam('openid_mode') == 'id_res') {
                $consumer = new Zend_OpenId_Consumer($storage );
                if ($consumer->verify($_GET)) {
                    $userId = $this->_getParam('id');
                    $this->_addUserIdentity($userId, $this->_getOpenidIdentity());
                    // $user = $this->site->getUser($id);
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

    protected function _getUsers()
    {
        $userOrder = $this->admin->getUserOrder();

        $users = array();
        foreach ($this->admin->getUserRoles() as $username => $role) {
            $user = $this->site->getUser($username);
            if (empty($user)) {
                continue;
            }
            $user['order'] = isset($userOrder[$username]) ? $userOrder[$username] : 999;
            $users[$username] = $user;
        }

        uasort($users, array('Ld_Utils', "sortByOrder"));

        return $users;
    }

}
