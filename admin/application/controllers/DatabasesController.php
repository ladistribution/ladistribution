<?php

require_once 'BaseController.php';

/**
 * Index controller
 */
class DatabasesController extends BaseController
{

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
