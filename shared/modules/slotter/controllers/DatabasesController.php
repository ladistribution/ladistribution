<?php

require_once 'BaseController.php';

/**
 * Index controller
 */
class Slotter_DatabasesController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, 'databases', 'manage')) {
            $this->_disallow();
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $this->appendTitle( $this->translate('Databases') );

        $databasesPage = $this->_container->findOneByLabel( $this->translate('Databases') );

        if ($databasesPage) {

            $databasesPage->addPage(array(
                'label' => 'New', 'module'=> 'slotter', 'controller' => 'databases', 'action' => 'new'
            ));
            $databasesPage->addPage(array(
                'label' => 'Master', 'module'=> 'slotter', 'controller' => 'databases', 'action' => 'master'
            ));
            $databasesPage->addPage(array(
                'label' => 'Create', 'module'=> 'slotter', 'controller' => 'databases', 'action' => 'create'
            ));
            if ($this->_hasParam('id')) {
                $action = $this->getRequest()->action;
                $databasesPage->addPage(array(
                    'label' => ucfirst($action),
                    'module'=> 'slotter',
                    'route' => 'default',
                    'controller' => 'databases',
                    'action' => $action,
                    'params' => array('id' => $this->_getParam('id'))
                ));
            }

        }

    }

    /**
     * Index action.
     */
    public function indexAction()
    {
        $this->view->databases = $this->site->getDatabases();
    }

    /**
     * New action.
     */
    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $db = array(
                'type' => $this->_getParam('type'),
                'host' => $this->_getParam('host'),
                'name' => $this->_getParam('name'),
                'user' => $this->_getParam('user'),
                'password' => $this->_getParam('password')
            );
            $this->site->addDatabase($db);
            $this->_redirector->goto('index');
        }
    }

    /**
     * Create action.
     */
    public function masterAction()
    {
        if ($this->getRequest()->isPost()) {
            $db = array(
                'type' => $this->_getParam('type'),
                'host' => $this->_getParam('host'),
                'user' => $this->_getParam('user'),
                'password' => $this->_getParam('password')
            );
            $this->site->addDatabase($db);
            $this->_redirector->goto('index');
        }
    }

    /**
     * Create action.
     */
    public function createAction()
    {
        $master = $this->site->getDatabase($this->_getParam('master'));
        if (empty($master)) {
            throw new Exception("Non existing master connection.");
        }

        if ($this->getRequest()->isPost()) {
            $db = array(
                'master'   => $this->_getParam('master'),
                'type'     => $this->_getParam('type'),
                'name'     => $this->_getParam('name'),
                'user'     => $this->_getParam('user'),
                'password' => $this->_getParam('password')
            );
            $this->site->createDatabase($db);
            $this->_redirector->goto('index');
        }

        $this->view->user = $this->view->name = Ld_Plugin::applyFilters('Slotter:createDatabase:name', 'ladistribution');
        $this->view->password = Ld_Auth::generatePhrase(12);
    }

    /**
     * Edit action.
     */
    public function editAction()
    {
        $this->view->id = $id = $this->_getParam('id');
        $databases = $this->site->getDatabases();
        if (empty($databases[$id])) {
            throw new Exception('Unknown database.');
        }
        $this->view->db = $databases[$id];

        if ($this->getRequest()->isPost()) {
            $params = array(
                'type' => $this->_getParam('type'),
                'host' => $this->_getParam('host'),
                'name' => $this->_getParam('name'),
                'user' => $this->_getParam('user')
            );
            $password = $this->_getParam('password');
            if (!empty($password)) {
                $params['password'] = $password;
            }
            $this->site->updateDatabase($id, $params);
            $this->_redirector->goto('index');
        }
    }

    /**
     * Delete action.
     */
    public function deleteAction()
    {
        $this->view->id = $id = $this->_getParam('id');
        $this->view->used = $used = $this->getSite()->isDatabaseUsed($id);
        if ($this->getRequest()->isPost()) {
            if ($used) {
                throw new Exception('Database currently used.');
            }
            $this->site->deleteDatabase($id);
            $this->_redirector->goto('index');
        }
    }

}
