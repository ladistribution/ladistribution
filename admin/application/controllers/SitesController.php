<?php

require_once 'BaseController.php';

/**
 * Site controller
 */
class SitesController extends BaseController
{

    /**
    * Index action.
    */
    public function indexAction()
    {
        $this->view->instances = array();

        $instances = $this->site->getInstances('application');

        foreach ($instances as $instance) {
            if ($instance['package'] != 'admin') {
                $this->view->instances[] = $this->site->getInstance($instance['path']);
            }
        }

        $this->site->availableSlots = (int)$this->site->slots - count($this->view->instances);

        $this->view->databases = $this->site->getDatabases();

        $this->view->repositories = $this->site->getRepositories('local');

        $this->view->users = $this->site->getUsers();

        $this->view->packages = $this->site->getPackages();
    }

    /**
    * New action.
    */
    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $filename = APPLICATION . '/../dist/configuration.json';
            if (file_exists($filename)) {
                $configuration = Zend_Json::decode(file_get_contents($filename));
            } else {
                $configuration = array();
            }
            if (empty($configuration['sites'])) {
                $configuration['sites'] = array();
            }
            $id = strtolower($this->_getParam('name'));
            $configuration['sites'][$id] = array(
                'type' => $this->_getParam('type'),
                'name' => $this->_getParam('name'),
                'endpoint' => $this->_getParam('endpoint'),
                'username' => $this->_getParam('username'),
                'password' =>  $this->_getParam('password')
            );
            file_put_contents($filename, Zend_Json::encode($configuration));
            $this->_redirect(LD_BASE_PATH . '/admin/');
        }
    }
    
}
