<?php

require_once 'BaseController.php';

/**
 * Site controller
 */
class Slotter_IndexController extends BaseController
{

    /**
    * Index action.
    */
    public function indexAction()
    {
        $this->restrict();

        $this->view->instances = array();

        $instances = $this->site->getInstances('application');

        foreach ($instances as $id => $instance) {
            if ($instance['package'] != 'admin') {
                $this->view->instances[$id] = $this->site->getInstance($id);
            }
        }

        $this->site->availableSlots = (int)$this->site->slots - count($this->view->instances);

        $this->view->databases = $this->site->getDatabases();

        $this->view->repositories = $this->site->getRepositories('local');

        $this->view->users = $this->site->getUsers();

        $this->view->packages = $this->site->getPackages();
    }

}
