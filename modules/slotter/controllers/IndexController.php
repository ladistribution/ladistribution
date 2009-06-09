<?php

require_once 'BaseController.php';

/**
 * Site controller
 */
class Slotter_IndexController extends BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, 'instances', 'view')) {
            $this->_disallow();
        }
    }

    /**
    * Index action.
    */
    public function indexAction()
    {
        $this->view->instances = array();

        $instances = $this->site->getInstances('application');

        foreach ($instances as $id => $instance) {
            if ($instance['package'] != 'admin') {
                $this->view->instances[$id] = $this->site->getInstance($id);
            }
        }

        $this->view->databases = $this->site->getDatabases();

        $this->view->users = $this->site->getUsers();

        $this->view->canAdminInstances = $this->_acl->isAllowed($this->userRole, null, 'admin');
    }

}
