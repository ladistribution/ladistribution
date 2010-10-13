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
            if (empty($this->instance)) {
                throw new Exception('Non existing instance.');
            }
        }

        if (!$this->_acl->isAllowed($this->userRole, 'instances', 'admin')) {
            $this->_disallow();
        }

        $this->_handleNavigation();

        if ($this->getRequest()->isXmlHttpRequest()) {
            Zend_Layout::getMvcInstance()->disableLayout();
        }
    }

    protected function _handleNavigation()
    {
        $translator = $this->getTranslator();

        $applicationsPage = $this->_container->findOneByLabel( $translator->translate('Applications') );
        $applicationsPage->addPage(array(
            'label' => $translator->translate("New"), 'module'=> 'slotter', 'controller' => 'instance', 'action' => 'new'
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
                'label' => ucfirst($translator->translate($action)),
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
                $this->view->packages = $this->site->getApplications();
                ksort($this->view->packages);
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

            $this->view->package = $package = $this->site->getPackageExtension($this->instance->getPackageId(), $extension);
            $this->view->preferences = $this->site->getInstallPreferences($package);

            if ($this->instance->hasExtension($extension)) {
                $this->view->installed = true;
                return;
            }

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
            $this->view->extension = $extension = $this->instance->getExtension($this->_getParam('update'));
            if ($this->getRequest()->isPost()) {
                if ($this->_hasParam('confirm')) {
                    $this->instance->updateExtension($extension);
                }
                $this->_redirectToAction('extensions');
            } else {
                $this->render('extension-update');
            }
            return;

        } else if ( $this->_hasParam('remove') ) {
            $this->view->extension = $extension = $this->instance->getExtension($this->_getParam('remove'));
            if ($this->getRequest()->isPost()) {
                if ($this->_hasParam('confirm')) {
                    $this->instance->removeExtension($extension);
                }
                $this->_redirectToAction('extensions');
            } else {
                $this->render('extension-remove');
            }
            return;

        }

        $extensions = $this->site->getPackageExtensions($this->instance->getPackageId());

        $this->view->extensions = array();
        foreach ($extensions as $id => $extension) {
            $this->view->extensions[$id] = $extension;
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

      if ($this->getRequest()->isPost() && $this->_hasParam('configuration')) {
          $configuration = $this->_getParam('configuration');
          $this->view->configuration = $this->instance->setConfiguration($configuration);
      } else {
          $this->view->configuration = $this->instance->getConfiguration();
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
          if ($this->instance->hasExtension($extension->id)) {
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

      $this->view->preferences = $this->instance->getInstaller()->getPreferences('theme');

      $this->view->themes = $this->instance->getThemes();
  }

  /**
   * Appearance action.
   */
  public function appearanceAction()
  {
      if ($this->getRequest()->isGet()) {
         $this->view->configuration = $this->instance->getConfiguration('theme');

      } else if ($this->getRequest()->isPost()) {
          if ($this->_hasParam('configuration')) {
              $configuration = $this->_getParam('configuration');
              $this->view->configuration = $this->instance->setConfiguration($configuration, 'theme');
          }
          if ($this->_hasParam('colors')) {
              $colors = Ld_Ui::getApplicationColors($this->instance);
              foreach ($this->_getParam('colors') as $key => $value) {
                  $colors[$key] = $value;
              }
              $colors['version'] = md5( serialize($colors) );
              $filename = $this->instance->getAbsolutePAth() . '/dist/colors.json';
              Ld_Files::putJson($filename, $colors);
          }
      }

      $this->view->preferences = $this->instance->getInstaller()->getPreferences('theme');
      $this->view->colorSchemes = $this->instance->getColorSchemes();

      $this->view->colors = Ld_Ui::getApplicationColors($this->instance);
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
          foreach ($availableBackups as $backup) {
              if ($backup['filename'] == $this->_getParam('download')) {
                  ob_end_clean();
                  header('Content-Type: application/zip');
                  header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
                  $handle = fopen($backup['absoluteFilename'], "rb");
                  while ( ($buffer = fread($handle, 8192)) != '' ) {
                      echo $buffer;
                  }
                  fclose($handle);
                  exit;
              }
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
      // if ($this->_hasParam('upload')) {
      //     $dir = $this->instance->getAbsolutePath() . '/backups/';
      //     Ld_Files::createDirIfNotExists($dir);
      //     $adapter = new Zend_File_Transfer_Adapter_Http();
      //     $adapter->setDestination($dir);
      //     $result = $adapter->receive();
      //     return $this->_redirectToAction('backups');
      // }

      $this->view->backups = $availableBackups;
  }

    /**
     * Delete action.
     */
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->site->deleteInstance($this->instance);
            $url = $this->view->url(array('controller' => 'index', 'action' => 'index'), 'default', true);
            $this->_redirectTo($url);
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
            // save user order
            if ($this->_hasParam('userOrder')) {
                $userOrder = array_merge($this->instance->getUserOrder(), (array)$this->_getParam('userOrder'));
                $this->instance->setUserOrder($userOrder);
            }
            // save user roles
            if ($this->_hasParam('userRoles')) {
                $userRoles = array_merge($this->instance->getUserRoles(), (array)$this->_getParam('userRoles'));
                $this->instance->setUserRoles($userRoles);
            }
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->noRender();
                $this->getResponse()->appendBody('ok');
                return;
            }
        }

        $this->view->users = $this->_getUsers();
        $this->view->roles = $this->instance->getRoles();
        $this->view->userRoles = $this->instance->getUserRoles();

        // Fix missing roles
        foreach ($this->view->users as $username => $user) {
            if (empty($this->view->userRoles[$username])) {
                $this->view->userRoles[$username] = $installer->defaultRole;
            }
        }
    }

    protected function _getUsers()
    {
        $users = $this->instance->getUsersByUsername();

        foreach ($this->admin->getUserRoles() as $username => $role) {
            if (empty($users[$username])) {
                $user = $this->site->getUser($username);
                if (empty($user)) {
                    continue;
                }
                $users[$username] = $user;
            }
        }

        $userOrder = $this->instance->getUserOrder();
        foreach ($users as $username => $user) {
            $users[$username]['order'] = isset($userOrder[$username]) ? $userOrder[$username] : 999;
        }

        uasort($users, array('Ld_Utils', "sortByOrder"));

        return $users;
    }

    public function cloneAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('upload')) {

            $url = $this->_getParam('url');

            if (!empty($url)) {
                $filename = LD_TMP_DIR . '/backup-' . date("d-m-Y-H-i-s") . '.zip';
                Ld_Http::download($url, $filename);
            } else {
                $filename = Ld_Http::upload();
            }

            $preferences = $this->_getParam('preferences');
            $preferences['path'] = Ld_Files::cleanpath($preferences['path']);

            $instance = $this->site->cloneInstance($filename, $preferences);

            $this->_redirectToAction('status', $instance->id);
        }
    }

    public function moveAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('path')) {
            $path = Ld_Files::cleanpath($this->_getParam('path'));
            $instance = $this->site->moveInstance($this->instance, $path);
            $this->_redirectToAction('status');
        }
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
