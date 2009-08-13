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

        if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
            $this->_disallow();
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $translator = $this->getTranslator();

        $databasesPage = $this->_container->findOneByLabel( $translator->translate('Databases') );

        $databasesPage->addPage(array(
            'label' => 'New', 'module'=> 'slotter', 'controller' => 'databases', 'action' => 'new'
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
        if ($this->getRequest()->isPost()) {
            $this->site->deleteDatabase($id);
            $this->_redirector->goto('index');
        }
    }

}
