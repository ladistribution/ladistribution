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
            $this->_redirector->gotoSimple('index', 'users');
        }
    }

    public function addIdentityAction()
    {
        $auth = Zend_Auth::getInstance();

        $root = 'http://' . $this->getRequest()->getServer('SERVER_NAME') . $this->getRequest()->getBaseUrl();

        $adapter = new Zend_Auth_Adapter_OpenId($this->_getParam('openid_identifier'), null, null, $root);

        $result = $auth->authenticate($adapter);

        if ($result->isValid()) {
            $id = $this->_getParam('id');
            $user = $this->site->getUser($id);
            if (empty($user['identities'])) {
                $user['identities'] = array();
            }
            $user['identities'][] = $auth->getIdentity();
            $this->site->updateUser($id, $user);
            $this->_redirector->gotoSimple('index', 'users');
        }

    }

}
