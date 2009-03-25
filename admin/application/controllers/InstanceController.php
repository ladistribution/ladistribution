<?php

require_once 'BaseController.php';

/**
 * Index controller
 */
class InstanceController extends BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $id = $this->id = $this->_getParam('id');
        if (isset($id)) {
            $this->view->instance = $this->instance = $this->site->getInstance($id);
        }
    }

    /**
    * New action.
    */
    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $packageId = $this->_getParam('packageId');
            $preferences = $this->_getParam('preferences');
            
            // TODO: valid every parameters
            $preferences['path'] = Ld_Files::cleanpath($preferences['path']);

            $this->view->instance = $this->instance = $this->site->createInstance($packageId, $preferences);
            if (defined('DEBUG') && constant('DEBUG') === true) {
                $this->render('ok'); // allow us to view install errors/warning/notices
            } else {
                $this->_redirectToAction('manage');
            }

        } else if ($this->getRequest()->isGet()) {
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

            $this->view->package = $package = $this->site->getPackageExtension($this->instance->package, $extension);
            $this->view->preferences = $this->site->getInstallPreferences($package);

            if ($this->getRequest()->isPost()) {
                $preferences = $this->_getParam('preferences');
                $this->site->addExtension($this->instance, $extension, $preferences);
                if ($this->_hasParam('referer')) {
                    $this->_redirect($this->_getParam('referer'));
                } else {
                    $this->_redirectToAction('manage');
                }
                return;
            }

        } else if ( $this->_hasParam('update') ) {
            $path = $this->_getParam('update');
            $this->instance->updateExtension($path);
            $this->render('ok');
            return;

        } else if ( $this->_hasParam('remove') ) {
            $path = $this->_getParam('remove');
            $this->instance->removeExtension($path);
            $this->_redirectToAction('manage');
            return;
        }

        $extensions = $this->site->getPackageExtensions($this->instance->package);

        $this->view->extensions = array();
        foreach ($extensions as $id => $extension) {
            if (!$this->_isExtensionInstalled($id)) {
                $this->view->extensions[$id] = $extension;
            }
        }
    }

    /**
     * Update action.
     */
    public function updateAction()
    {
        // TODO: update only from a POST
        // $this->instance->update();
        $this->site->updateInstance($this->instance);
        $this->_redirectToAction('manage');
    }

  /**
   * Manage action.
   */
  public function manageAction()
  {
      $this->view->extensions = $this->instance->getExtensions();

      // Need Update ?
      $package = $this->site->getPackage($this->instance->package);
      $this->instance->update = $this->instance->version != $package->version ? $package->version : false;

      foreach ($this->view->extensions as $id => $extension) {
          $this->view->extensions[$id]->update = $this->_needExtensionUpdate($extension);
      }
  }

  /**
   * Configure action.
   */
  public function configureAction()
  {
      if ( $this->_hasParam('restrict') ) {
          $this->site->restrictInstance($this->instance, (bool)$this->_getParam('restrict'));
          $this->_redirectToAction('manage');
          //$this->render('ok');
      }
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
      $extensions = $this->site->getPackageExtensions($this->instance->package, 'theme');
      
      // $extensions = Ld_Packages::getExtensions($this->instance->package, 'theme');
      
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
              $this->site->setTheme($this->instance, $theme);
          }
          if ($this->_hasParam('configuration')) {
              $configuration = $this->_getParam('configuration');
              $this->view->configuration = $this->instance->setConfiguration($configuration, 'theme');
          }
      }
      
      $this->view->preferences = $this->instance->getPreferences('theme');
      
      $this->view->themes = $this->site->getThemes($this->instance);
  }
  
  /**
   * Backup action.
   */
  public function backupAction()
  {
      $this->site->backupInstance($this->instance);
      $this->_redirectToAction('manage');
  }

  /**
   * Restore action.
   */
  public function restoreAction()
  {
      $archive = $this->_getParam('archive');
      if (isset($archive)) {
          $this->site->restoreBackup($this->instance, $archive);
          $this->_redirectToAction('manage');
      } else {
          $this->view->archives = $this->site->getBackups($this->instance);
      }
  }
  
  /**
   * Delete action.
   */
  public function deleteAction()
  {   
      if (empty($this->instance)) {
          return false;
      }
      
      $this->site->deleteInstance($this->instance);
      
      // $this->render('ok');
      
      $url = $this->view->url(array('site' => $this->view->site->id), 'site');
      $this->_redirect($url, array('prependBase' => false));
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
          if ($extension->package == $id) {
              return true;
          }
      }
      return false;
  }

  protected function _needExtensionUpdate($extension)
  {
      try {
          $package = $this->site->getPackageExtension($this->instance->package, $extension->package);
          return $extension->version != $package->version;
      } catch (Exception $e) {
          return false;
      }
  }

  protected function _redirectToAction($action)
  {
      $url = $this->view->instanceActionUrl($action);
      $this->_redirect($url, array('prependBase' => false));
      $this->render('ok');
  }

}
