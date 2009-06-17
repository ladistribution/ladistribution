<?php

require_once 'BaseController.php';

/**
 * Index controller
 */
class Slotter_InstanceController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $id = $this->view->id = $this->id = $this->_getParam('id');
        if (isset($id)) {
            $this->view->instance = $this->instance = $this->site->getInstance($id);
        }

        if (!$this->_acl->isAllowed($this->userRole, 'instances', 'admin')) {
            $this->_disallow();
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $applicationsPage = $this->_container->findOneByLabel('Applications');
        $applicationsPage->addPage(array(
            'label' => 'New', 'module'=> 'slotter', 'controller' => 'instance', 'action' => 'new'
        ));
        if (isset($this->id, $this->instance)) {
            $action = $this->getRequest()->action;
            $instancePage = new Zend_Navigation_Page_Mvc(array(
                'label' => $this->instance->getName(),
                'module'=> 'slotter',
                'route' => 'instance-action',
                'controller' => 'instance',
                'params' => array('id' => $this->id)
            ));
            $instancePage->addPage(array(
                'label' => ucfirst($action),
                'module'=> 'slotter',
                'route' => 'instance-action',
                'controller' => 'instance',
                'action' => $action
            ));
            $applicationsPage->addPage($instancePage);
        }
    }

    /**
    * New action.
    */
    public function newAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('packageId')) {
            $packageId = $this->_getParam('packageId');
            $preferences = $this->_getParam('preferences');

            // TODO: valid every parameters
            $preferences['path'] = Ld_Files::cleanpath($preferences['path']);

            $this->view->instance = $this->instance = $this->site->createInstance($packageId, $preferences);

            $this->_redirectToAction('status', $this->instance->id);

        } else {
            if ($this->_hasParam('packageId')) {
                $packageId = $this->_getParam('packageId');
                $this->view->package = $this->site->getPackage($packageId);
                $this->view->preferences = $this->site->getInstallPreferences($packageId);
            } else {
                $this->view->packages = $this->site->getPackages();
            }
        }
    }

    /**
    * Extensions action.
    */
    public function extensionsAction()
    {
        if ( $this->_hasParam('add') ) {

            $this->view->extension = $extension = $this->_getParam('add');
            if ($this->_isExtensionInstalled($extension)) {
                $this->view->installed = true;
                return;
            }

            $this->view->package = $package = $this->site->getPackageExtension($this->instance->getPackageId(), $extension);
            $this->view->preferences = $this->site->getInstallPreferences($package);

            if ($this->getRequest()->isPost()) {
                $preferences = $this->_getParam('preferences');
                $this->instance->addExtension($extension, $preferences);
                if ($this->_hasParam('referer')) {
                    $this->_redirectTo($this->_getParam('referer'));
                } else {
                    $this->_redirectToAction('status');
                }
                return;
            }

        } else if ( $this->_hasParam('update') ) {
            $path = $this->_getParam('update');
            $this->instance->updateExtension($path);
            $this->_redirectToAction('status');
            return;

        } else if ( $this->_hasParam('remove') ) {
            $path = $this->_getParam('remove');
            $this->instance->removeExtension($path);
            $this->_redirectToAction('status');
            return;
        }

        $extensions = $this->site->getPackageExtensions($this->instance->getPackageId());

        $this->view->extensions = array();
        foreach ($extensions as $id => $extension) {
            if (!$this->_isExtensionInstalled($id) && $extension->type != 'theme') {
                $this->view->extensions[$id] = $extension;
            }
        }
    }

    /**
     * Update action.
     */
    public function updateAction()
    {
        // TODO: we should update only from a POST
        $this->site->updateInstance($this->instance);
        $this->_redirectToAction('status');
    }

    /**
    * Status action.
    */
    public function statusAction()
    {
        $this->view->extensions = $this->instance->getExtensions();
        foreach ($this->view->extensions as $id => $extension) {
            $this->view->extensions[$id]->hasUpdate = $extension->hasUpdate();
        }
    }

    public function manageAction()
    {
        $this->_forward('status');
    }

  /**
   * Configure action.
   */
  public function configureAction()
  {
      $this->view->preferences = $this->instance->getPreferences('configuration');
      
      if ($this->getRequest()->isGet()) {
          $this->view->configuration = $this->instance->getConfiguration();
      
      } else if ($this->getRequest()->isPost()) {
          $configuration = $this->_getParam('configuration');
          $this->view->configuration = $this->instance->setConfiguration($configuration);
      }
  }
  
  /**
   * Themes action.
   */
  public function themesAction()
  {
      $extensions = $this->site->getPackageExtensions($this->instance->getPackageId(), 'theme');
      
      $this->view->extensions = array();
      foreach ($extensions as $extension) {
          if ($this->_isExtensionInstalled($extension->id)) {
              continue;
          }
          $this->view->extensions[] = $extension;
      }
      
      if ($this->getRequest()->isGet()) {
         $this->view->configuration = $this->instance->getConfiguration('theme');
         
      } else if ($this->getRequest()->isPost()) {
          if ($this->_hasParam('theme')) {
              $theme = $this->_getParam('theme');
              $this->instance->setTheme($theme);
          }
          if ($this->_hasParam('configuration')) {
              $configuration = $this->_getParam('configuration');
              $this->view->configuration = $this->instance->setConfiguration($configuration, 'theme');
          }
      }
      
      $this->view->preferences = $this->instance->getPreferences('theme');
      
      $this->view->themes = $this->instance->getThemes();
  }
  
  /**
   * Backup action.
   */
  public function backupsAction()
  {
      $availableBackups = $this->instance->getBackups();

      // Do backup
      if ($this->getRequest()->isPost() && $this->_hasParam('dobackup')) {
          $this->instance->doBackup();
          return $this->_redirectToAction('backups');
      }

      // Download
      if ($this->_hasParam('download')) {
          $backup = $this->_getParam('download');
          if (in_array($backup, $availableBackups)) {
              $filename =  $this->instance->getAbsolutePath() . '/backups/' . $backup;
              header('Content-Type: application/zip');
              header('Content-Disposition: attachment; filename="' . $backup . '"');
              echo file_get_contents($filename);
              exit;
          }
          throw new Exception('Non existing backup.');
      }

      // Delete
      if ($this->_hasParam('delete')) {
          $this->instance->deleteBackup( $this->_getParam('delete') );
          return $this->_redirectToAction('backups');
      }

      // Restore
      if ($this->_hasParam('restore')) {
          $this->instance->restoreBackup( $this->_getParam('restore') );
          return $this->_redirectToAction('backups');
      }

      // Upload
      if ($this->_hasParam('upload')) {
          $dir = $this->instance->getAbsolutePath() . '/backups/';
          Ld_Files::createDirIfNotExists($dir);
          $adapter = new Zend_File_Transfer_Adapter_Http();
          $adapter->setDestination($dir);
          $result = $adapter->receive();
          return $this->_redirectToAction('backups');
      }

      $this->view->backups = $availableBackups;
  }

    /**
     * Delete action.
     */
    public function deleteAction()
    {
        if (empty($this->instance)) {
            return false;
        }
        if ($this->getRequest()->isPost()) {
            $this->site->deleteInstance($this->instance);
            $this->_redirector->setGotoSimple('index', 'index');
        }
    }

  public function rolesAction()
  {
      $methods = array('getRoles', 'getUserRoles', 'setUserRoles');
      
      $installer = $this->instance->getInstaller();
      foreach ($methods as $method) {
          if (!method_exists($installer, $method)) {
              $this->view->supported = false;
              return;
          }
      }
      
      if ($this->getRequest()->isPost()) {
          $roles = $this->_getParam('roles');
          $this->instance->getInstaller()->setUserRoles($roles);
      }
      
      $this->view->users = $this->site->getUsers();
      $this->view->roles = $this->instance->getInstaller()->getRoles();
      $this->view->userRoles = $this->instance->getInstaller()->getUserRoles();
  }
  
  // public function cloneAction()
  // {
  //         $preferences = $this->_getParam('preferences');
  //         $this->view->instance = $instance = $this->site->createInstance($packageId, $preferences);
  //         foreach ($extensions as $extension) {
  //             $this->site->extendInstance($instance, $extension['package']);
  //         }
  //         $this->site->restoreBackup($instance, $archive, true);
  // }

  protected function _isExtensionInstalled($id)
  {
      $extensions = $this->instance->getExtensions();
      foreach ($extensions as $extension) {
          if ($extension->getPackageId() == $id) {
              return true;
          }
      }
      return false;
  }

    protected function _redirectToAction($action = 'status', $id = null)
    {
        $url = $this->view->instanceActionUrl($action, $id);
        $this->_redirectTo($url);
    }

    protected function _redirectTo($url)
    {
        if (constant('LD_DEBUG')) {
            $this->view->redirectUrl = $url;
            $this->render('ok');
        } else {
            $this->_redirector->gotoUrl($url, array('prependBase' => false));
        }
    }

}
