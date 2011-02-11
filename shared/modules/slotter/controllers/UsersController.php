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
            case 'remove-trusted':
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

        $this->appendTitle( $translator->translate('Users') );

        $usersPage = $this->_container->findOneByLabel( $translator->translate('Users') );

        $usersPage->addPage(array(
            'label' => $translator->translate('Users'), 'module'=> 'slotter', 'controller' => 'users', 'action' => 'index'
        ));

        $usersPage->addPage(array(
            'label' => $translator->translate('Roles'), 'module'=> 'slotter', 'controller' => 'users', 'action' => 'roles'
        ));

        $usersPage->addPage(array(
            'label' => $translator->translate('Your Profile'), 'module'=> 'slotter', 'controller' => 'users', 'action' => 'edit',
            'params' => array('id' => $this->currentUser['username'])
        ));

        if ($this->_hasParam('id') && $this->_getParam('id') != $this->currentUser['username']) {
            $indexPage = $usersPage->findOneByLabel( $translator->translate('Users') );
            $action = $this->getRequest()->action;
            $indexPage->addPage(array(
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

        $this->view->users = $this->admin->getUsers();
        $this->view->roles = $this->admin->getRoles();
        $this->view->userRoles = $this->admin->getUserRoles();

        $this->view->canManageRoles = $this->_acl->isAllowed($this->userRole, 'instances', 'manage');
        $this->view->canManageUsers = $this->_acl->isAllowed($this->userRole, 'users', 'manage');
    }

    protected function _getApplications()
    {
        $applications = $this->site->getApplicationsInstances(array('admin'));
        $applications = array_merge(array('admin' => $this->admin), $applications);
        return $applications;
    }

    public function rolesAction()
    {

        if ($this->getRequest()->isPost()) {
            $roles = $this->_getParam('roles');
            foreach ($this->_getApplications() as $id => $instance) {
                if (isset($roles[$id])) {
                    $instance->setUserRoles($roles[$id]);
                }
            }
            $this->_redirector->gotoSimple('roles', 'users');
            return;
        }

        $this->view->users = $this->admin->getUsers();

        $applications = array();
        foreach ($this->_getApplications() as $id => $instance) {
            $applications[$id]['name'] = $instance->getName();
            $applications[$id]['path'] = $instance->getPath();
            $applications[$id]['roles'] = $instance->getRoles();
            $applications[$id]['userRoles'] = $instance->getUserRoles();
            // Fix missing roles
            $defaultRole = $instance->getInstaller()->defaultRole;
            foreach ($this->view->users as $username => $user) {
                if (empty( $applications[$id]['userRoles'][$username])) {
                    $applications[$id]['userRoles'][$username] = $defaultRole;
                }
            }
        }

        $this->view->applications = $applications;

        $this->view->canManageRoles = $this->_acl->isAllowed($this->userRole, 'instances', 'manage');
        $this->view->canManageUsers = $this->_acl->isAllowed($this->userRole, 'users', 'manage');
    }

    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $user = array(
                'origin'     => 'Slotter:Users:new',
                'username'   => $this->_getParam('username'),
                'password'   => $this->_getParam('password'),
                'fullname'   => $this->_getParam('fullname'),
                'email'      => $this->_getParam('email')
            );
            $this->site->addUser($user);

            $username = $user['username'];

            // if it's the first user, make him administrator.
            $users = $this->site->getUsers();
            if (count($users) == 1) {
                $this->admin->setUserRoles(array($username => 'admin'));
                Ld_Auth::authenticate($user['username'], $user['password']);
                // in this case, we believe the user wants to go back to the index
                $this->_redirector->gotoSimple('index', 'index');
                return;
            } else {
                $userRoles = $this->admin->getUserRoles();
                $userRoles[$username] = 'user';
                $this->admin->setUserRoles($userRoles);
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
            $users = $this->view->users = $this->admin->getUsers();
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

        $this->view->user = $user = $this->site->getUser($id);

        $this->view->identity = $this->admin->getIdentityUrl($id);

        $openidProvider = $this->admin->getOpenidProvider($user['username'], true);
        $this->view->trustedSites = $openidProvider->getTrustedSites();
    }

    public function addIdentityAction()
    {
        $root = 'http://' . $this->getRequest()->getServer('SERVER_NAME') . $this->getRequest()->getBaseUrl();

        if ($this->_hasParam('openid_identifier')) {

            $consumer = $this->admin->getOpenidConsumer();
            if (!$consumer->login($this->_getParam('openid_identifier'), null, $root)) {
                throw new Exception("OpenID login failed: " . $consumer->getError());
            }

        } elseif ($this->_hasParam('openid_mode')) {

            if ($this->_getParam('openid_mode') == 'id_res') {
                $consumer = $this->admin->getOpenidConsumer();
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

    public function emailRegexp()
    {
        return "/([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]" . 
            "+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)/i";
    }

    public function inviteAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('emails')) {
            $emails = $this->_getParam('emails');

            if ($this->_hasParam('confirm')) {
                $roles = $this->_getParam('roles');
                $userRoles = $this->admin->getUserRoles();
                foreach ($emails as $email) {
                    $explode = explode('@', $email);
                    $username = $explode[0];
                    $user = array(
                        'origin'     => 'Slotter:invite',
                        'username'   => $username,
                        'password'   => Ld_Auth::generatePhrase(),
                        'fullname'   => '',
                        'email'      => $email,
                        'token'      => Ld_Auth::generatePhrase(),
                        'activated'  => false
                    );
                    $this->site->addUser($user, false);
                    $this->sendInvitationEmail($user);
                    $userRoles[$username] = isset($roles[$email]) ? $roles[$email] : 'user';
                }
                $this->admin->setUserRoles($userRoles);
                $this->render('invite-ok');
            } else {
                preg_match_all($this->emailRegexp(), $emails, $matches);
                if (!empty($matches[0])) {
                    $usersBackend = $this->site->getUsersBackend();
                    $this->view->emails = array();
                    foreach ($matches[0] as $email) {
                        $this->view->emails[$email] = $usersBackend->getUserByEmail($email);
                    }
                }
            }
        }

        $this->view->roles = $this->admin->getRoles();
        $this->view->userRoles = $this->admin->getUserRoles();
    }

    public function sendInvitationEmail($user)
    {
        $activationUrl = $this->admin->buildUrl(array(
            'module' => 'default', 'controller' => 'auth', 'action' => 'activate', 'token' => $user['token']));

        $text =
            'I just created an account for you to join ' . $this->site->getName() . "\n" .
            $this->site->getUrl() . "\n" .
            "\n" .
            "You can activate your account by clicking on the link below." . "\n" .
            $activationUrl;

        $mail = new Zend_Mail('UTF-8');
        $mail = Ld_Plugin::applyFilters('Slotter:mail', $mail);
        $mail->setFrom($this->currentUser['email'], $this->currentUser['fullname']);
        $mail->addTo($user['email']);
        $mail->setSubject('Invitation to join ' . $this->site->getName());
        $mail->setBodyText($text);
        $mail->send();
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

    public function removeTrustedAction()
    {
        $id = $this->_getParam('id');
        $siteroot = urldecode( $this->_getParam('siteroot') );

        $user = $this->site->getUser($id);
        $openidProvider = $this->admin->getOpenidProvider($user['username'], true);
        $openidProvider->delSite($siteroot);

        $this->_redirector->gotoSimple('edit', 'users', 'slotter', array('id' => $id));
    }

}
