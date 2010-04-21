<?php

require_once 'BaseController.php';

/**
 * Site controller
 */
class Slotter_IndexController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        switch ($this->getRequest()->action) {
            case 'index':
                // disallow anonymous access to instance list ?
                if (!$this->_acl->isAllowed($this->userRole, 'instances', 'view')) {
                    // $this->_disallow();
                }
                break;
            default:
                if (!$this->_acl->isAllowed($this->userRole, 'instances', 'admin')) {
                    $this->_disallow();
                }
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $translator = $this->getTranslator();

        $applicationsPage = $this->_container->findOneByLabel( $translator->translate('Applications') );
        $applicationsPage->addPage(array(
            'label' =>  $translator->translate('Update'), 'module'=> 'slotter', 'controller' => 'index', 'action' => 'update'
        ));
    }

    /**
    * Index action.
    */
    public function indexAction()
    {
        $this->view->hasUpdate = false;

        $applications = $this->site->getApplicationsInstances();
        foreach ($applications as $id => $instance) {
            if ($instance->hasUpdate = $instance->hasUpdate()) {
                $this->view->hasUpdate = true;
            }
        }

        $this->view->applications = $this->site->getApplicationsInstances(array('admin'));

        $this->view->databases = $this->site->getDatabases();

        $this->view->roles = $this->admin->getUserRoles();

        $this->view->repositories = $this->site->getRepositories();

        $this->view->canAdmin = $this->_acl->isAllowed($this->userRole, null, 'admin');

        $this->view->canManageDatabases = $this->_acl->isAllowed($this->userRole, 'databases', 'manage');
        $this->view->canManageRepositories = $this->_acl->isAllowed($this->userRole, 'repositories', 'manage');
        $this->view->canManagePlugins = $this->_acl->isAllowed($this->userRole, 'plugins', 'manage');
    }

    /**
     * Order action.
     */
    public function orderAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost() && $this->_hasParam('app')) {
            $instances = $this->site->getInstances();
            foreach ($this->_getParam('app') as $order => $id) {
                $instances[$id]['order'] = $order;
            }
            $this->site->updateInstances($instances);
            $this->noRender();
            $this->getResponse()->appendBody('ok');
        }
    }

    public function packagesAction()
    {
        $this->view->packages = $this->site->getPackages();
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
                $this->site->updateInstance($packageId);
            }
            $applications = (array)$this->_getParam('applications');
            $extensions = (array)$this->_getParam('extensions');
            foreach ($applications as $id => $state) {
                $instance = $this->site->getInstance($id);
                if ($instance->hasUpdate()) {
                    $this->site->updateInstance($instance);
                }
                if (isset($extensions[$id])) {
                    foreach ($extensions[$id] as $packageId => $state) {
                        $instance->updateExtension($packageId);
                    }
                }
            }
            // $this->_redirector->gotoSimple('update', 'index');
        }

        $instances = $this->site->getInstances();
        foreach ($instances as $id => $infos) {

            // Applications
            if ($infos['type'] == 'application') {
                $instance = $this->site->getInstance($id);
                if (empty($instance)) {
                    continue;
                }
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
