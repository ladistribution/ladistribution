<?php

require_once 'BaseController.php';

/**
 * Users controller
 */
class Slotter_RepositoriesController extends Slotter_BaseController
{

    /**
     * Init.
     */
    public function init()
    {
        parent::init();

        if ($this->_hasParam('id')) {
            $this->view->repository = $this->repository = $this->getSite()->getRepository( $this->_getParam('id') );
        }
    }

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        // HACK: If a repository URL is used as a public URL
        if (isset($this->repository) && strpos($_SERVER['REQUEST_URI'], 'packages.json') !== false) {
            if ($this->repository->type == 'local') {
                echo Ld_Files::get($this->repository->getDir() . '/packages.json');
            } else {
                echo Ld_Http::get($this->repository->getUrl() . '/packages.json');
            }
            exit;
        }

        if (!$this->_acl->isAllowed($this->userRole, 'repositories', 'manage')) {
            $this->_disallow();
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $this->appendTitle( $this->translate('Repositories') );

        if ( $repositoriesPage = $this->_container->findOneByLabel( $this->translate('Repositories') ) ) {

            $repositoriesPage->addPage(array(
                'label' => 'New', 'module'=> 'slotter', 'controller' => 'repositories', 'action' => 'new'
            ));

            if ($this->_hasParam('id')) {
                $id = $this->repository->type == 'local' ? $this->repository->name : $this->repository->getUrl();
                $label = sprintf("%s (%s)", $id, $this->repository->type);
                $action = $this->getRequest()->action;
                $repositoriesPage->addPage(array(
                    'label' => $label,
                    'module'=> 'slotter',
                    'route' => 'default',
                    'controller' => 'repositories',
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
        $this->view->repositories = $this->getSite()->getRepositories();
    }

    /**
     * Order action.
     */
    public function orderAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost() && $this->_hasParam('repositories')) {

            $repositories = $this->getSite()->getRawRepositories();
            $order = 0;
            foreach ($this->_getParam('repositories') as $id) {
                if (isset($repositories[$id])) {
                    if (empty($repositories[$id]['order']) || $repositories[$id]['order'] != $order) {
                        $repositories[$id]['order'] = $order;
                        $this->getSite()->getModel('repositories')->update($id, $repositories[$id]);
                    }
                    $order ++;
                }
            }

            $this->noRender();
            $this->getResponse()->appendBody('ok');
        }
    }

    /**
     * New action.
     */
    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->site->addRepository(array(
                'id' => $this->_getParam('name'),
                'type' => $this->_getParam('type'),
                'name' => $this->_getParam('name'),
                'endpoint' => trim($this->_getParam('endpoint'))
            ));
            $this->_redirector->setGotoSimple('index');
            $this->_redirector->redirectAndExit();
        }

        if ($this->getRequest()->isGet()) {
            $this->view->type = $this->_getParam('type', 'local');
        }
    }

    /**
     * Delete action.
     */
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $id = $this->repository->id;
            $this->getSite()->getModel('repositories')->delete($id);
            $this->_redirector->setGotoSimple('index');
            $this->_redirector->redirectAndExit();
        }
    }

    /**
     * Manage action.
     */
    public function manageAction()
    {
        if ($this->_hasParam('new')) {
            $type = $this->_getParam('new');
            if (in_array($type, array('application', 'library'))) {
                return $this->_newPackage($type);
            }
        }

        if ($this->_hasParam('upload')) {
            $filename = Ld_Http::upload();
            $this->repository->importPackage($filename);
        }

        if ($this->_hasParam('delete')) {
            $this->view->packageId = $packageId = $this->_getParam('delete');
            if ($this->getRequest()->isGet()) {
                $this->render('delete-package');
                return;
            }
            if ($this->getRequest()->isPost()) {
                $this->repository->deletePackage($packageId);
                $this->_redirector->gotoSimpleAndExit('manage', null, null, array('id' => $this->repository->id));
            }
        }

        // if ($this->_hasParam('package')) {
        //
        //     $type = $this->_getParam('type', 'application');
        //     $package = $this->_getParam('package');
        //
        //     $this->view->package = $package;
        //     $this->view->releases = $this->repository->getReleases($package, $type);
        //     $this->view->extensions = $this->repository->getPackageExtensions($package);
        //
        //     $this->render('package');
        // }

        $this->view->repository = $this->repository;

        $this->view->applications = $this->repository->getApplications();
        $this->view->extensions = $this->repository->getExtensions();
        $this->view->libraries = $this->repository->getLibraries();

        if ($this->repository->type != 'local') {
            $this->render('manage-remote');
        }
    }

}
