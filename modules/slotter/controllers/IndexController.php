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
        $this->view->applications = array();

        $this->view->hasUpdate = false;

        $applications = $this->site->getInstances('application');

        foreach ($applications as $id => $application) {
            $instance = $this->site->getInstance($id);
            if ($instance->hasUpdate = $instance->hasUpdate()) {
                $this->view->hasUpdate = true;
            }
            if ($application['package'] != 'admin') {
                $this->view->applications[$id] = $instance;
            }
        }

        $this->view->databases = $this->site->getDatabases();

        $this->view->users = $this->site->getUsers();

        $this->view->canAdminInstances = $this->_acl->isAllowed($this->userRole, null, 'admin');
    }

    /**
    * Update action.
    */
    public function updateAction()
    {
        $this->view->applications = array();
        $this->view->libraries = array();

        if ($this->getRequest()->isPost()) {
            $instances = $this->site->getInstances();
            $libraries = (array)$this->_getParam('libraries');
            foreach ($libraries as $packageId => $state) {
                print_r($packageId); echo '<br>';
                $this->site->updateInstance($packageId);
            }
            $applications = (array)$this->_getParam('applications');
            $extensions = (array)$this->_getParam('extensions');
            foreach ($applications as $id => $state) {
                $instance = $this->site->getInstance($id);
                print_r($id); echo '<br>';
                if ($instance->hasUpdate()) {
                    $this->site->updateInstance($instance);
                }
                if (isset($extensions[$id])) {
                    foreach ($extensions[$id] as $packageId => $state) {
                        print_r($packageId); echo '<br>';
                        $instance->updateExtension($packageId);
                    }
                }
            }
        }

        $instances = $this->site->getInstances();
        foreach ($instances as $id => $infos) {

            // Applications
            if ($infos['type'] == 'application') {
                $instance = $this->site->getInstance($id);
                $instance->hasUpdate = $instance->hasUpdate();
                if (empty($instance->hasUpdate)) {
                    // Extensions
                    foreach ($instance->getExtensions() as $extension) {
                        if ($extension->hasUpdate()) {
                            $instance->hasUpdate = true;
                            break;
                        }
                    }
                }
                if ($instance->hasUpdate || $this->_hasParam('all'))
                    $this->view->applications[$id] = $instance;

            // Libraries
            } else {
                $instance = new Ld_Instance_Library($infos);
                $instance->setSite($this->site);
                if (($instance->hasUpdate = $instance->hasUpdate()) || $this->_hasParam('all'))
                    $this->view->libraries[] = $instance;
            }
        }

        $this->view->all = $this->_hasParam('all');
    }

}
