<?php

require_once 'BaseController.php';

/**
 * Index controller
 */
class Slotter_DatabasesController extends BaseController
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
            $this->_forward('index');
        }
    }

}
