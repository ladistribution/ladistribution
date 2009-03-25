<?php

require_once 'BaseController.php';

/**
 * Users controller
 */
class RepositoriesController extends BaseController
{

    /**
     * Collect non existing actions.
     */
    // public function __call($method, $args)
    // {
    //     $this->_forward('index');
    // }

    /**
     * Init.
     */    
    public function init()
    {
        parent::init();
        
        if ($this->_hasParam('id')) {
            $id = $this->_getParam('id');
            $repositories = $this->site->getRepositories();
            $this->view->repository = $this->repository = $repositories[$id];
        }
    }

    /**
     * Index action.
     */
    public function indexAction()
    {
        $this->view->localRepositories = $this->site->getRepositories('local');
        $this->view->remoteRepositories = $this->site->getRepositories('remote');
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
                'endpoint' => $this->_getParam('endpoint')
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
        $this->site->removeRepository($this->repository->id);
        // TODO: replace by a redirect
        // $this->_forward('index');
        
        $this->_redirector->setGotoSimple('index');
        $this->_redirector->redirectAndExit();
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
            
            $dir = LD_TMP_DIR . '/uploads';
            Ld_Files::createDirIfNotExists($dir);
            
            $adapter = new Zend_File_Transfer_Adapter_Http();
            $adapter->setDestination($dir);
            $result = $adapter->receive();

            // if (!$result) {
            //     $messages = $adapter->getMessages();
            //     echo implode("\n", $messages);
            // }
            
            $this->repository->importPackage( $adapter->getFileName() );
        }
        
        if ($this->_hasParam('package')) {
            
            $type = $this->_getParam('type', 'application');
            $package = $this->_getParam('package');
            
            $this->view->package = $package;
            $this->view->releases = $this->repository->getReleases($package, $type);
            $this->view->extensions = $this->repository->getPackageExtensions($package);

            $this->render('package');
        }
        
        $this->view->applications = $this->repository->getApplications();
        
        $this->view->libraries = $this->repository->getLibraries();
        
        // $libraryTypes = array(
        //     'shared' => array('name' => 'Mixed (shared)'),
        //     'lib'    => array('name' => 'PHP (lib)'),
        //     'css'    => array('name' => 'CSS'),
        //     'js'     => array('name' => 'JS')
        // );
        // 
        // foreach ($libraryTypes as $id => $type) {
        //     $libraryTypes[$id]['libraries'] = $this->repository->getLibraries($id);
        // }
        // 
        // $this->view->libraryTypes = $libraryTypes;

    }

    /**
     * New package action.
     */
    // public function _newPackage($type)
    // {
    //     if ($this->getRequest()->isPost()) {
    //         $name = $this->_getParam('name');
    //         $type = $this->_getParam('type');
    //         $this->repository->createPackage($name, $type);
    //         // TODO: replace by a redirect
    //         return;
    //     }
    //     $this->view->type = $type;
    //     $this->render('new-package');
    // }

}