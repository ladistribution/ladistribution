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
            Zend_Registry::set('application', $this->instance);
            if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
                $this->view->headLink()->appendStylesheet(Ld_Ui::getApplicationStyleUrl(), 'screen');
            }
            $this->appendTitle( $this->instance->getName() );
        }

        if (!$this->_acl->isAllowed($this->userRole, 'instances', 'admin')) {
            $this->_disallow();
        }

        $this->_handleNavigation();

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->disableLayout();
        }
    }

    protected function _handleNavigation()
    {
        $applicationsPage = $this->_container->findOneByLabel( $this->translate('Applications') );
        $applicationsPage->addPage(array(
            'label' => $this->translate("Add"), 'module'=> 'slotter', 'controller' => 'instance', 'action' => 'new'
        ));
        $applicationsPage->addPage(array(
            'label' => $this->translate("Clone"), 'module'=> 'slotter', 'controller' => 'instance', 'action' => 'clone'
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
                'label' => ucfirst($this->translate($action)),
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

            if ($this->instance && $this->instance->getType() == 'application') {
                $this->_redirectTo( $this->instance->getUrl() );
            } else {
                $this->_redirector->gotoSimple('index', 'index', 'slotter');
            }

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
                $this->_flashMessenger->addMessage( $this->translate("Extension installed") );
                if ($this->_hasParam('referer')) {
                    $this->_redirectTo($this->_getParam('referer'));
                } else {
                    $this->_redirectToAction('configure');
                }
                return;
            }

        } else if ( $this->_hasParam('update') ) {
            $this->view->extension = $extension = $this->instance->getExtension($this->_getParam('update'));
            if ($this->getRequest()->isPost()) {
                if ($this->_hasParam('confirm')) {
                    $this->instance->updateExtension($extension);
                    $this->_flashMessenger->addMessage( $this->translate("Extension updated") );
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
                    $this->_flashMessenger->addMessage( $this->translate("Extension uninstalled") );
                }
                $this->_redirectToAction('extensions');
            } else {
                $this->render('extension-remove');
            }
            return;

        }

        $extensions = $this->site->getPackageExtensions($this->instance->getPackageId(), 'plugin');

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
        $this->_flashMessenger->addMessage( $this->translate("Instance updated") );
        $this->_redirectToAction('configure');
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
          $this->view->updateConfirmation = $this->translate("Configuration updated");
      } else {
          $this->view->configuration = $this->instance->getConfiguration();
      }
  }

  /**
   * Themes action.
   */
  public function themesAction()
  {
      if ($this->_hasParam('add')) {
          return $this->_forward('extensions');
      }

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

      $this->view->availableThemes = $this->site->getPackageExtensions($this->instance->getPackageId(), 'theme');
  }

  /**
   * Appearance action.
   */
  public function appearanceAction()
  {
      $application = $this->instance;
      $installer = $application->getInstaller();

      if ($this->getRequest()->isPost()) {
          if ($this->_hasParam('configuration')) {
              $configuration = $this->_getParam('configuration');
              $this->view->configuration = $application->setConfiguration($configuration, 'theme');
          }
          if ($this->_hasParam('colors')) {
              $colors = $application->getColors();
              foreach ($this->_getParam('colors') as $key => $value) {
                  $colors[$key] = $value;
              }
              $application->setColors($colors);
          }
          $this->_flashMessenger->addMessage( $this->translate("Colors updated") );
          return $this->_redirectToAction('appearance');
      }

      $this->view->preferences = $installer->getPreferences('theme');
      $this->view->configuration = $application->getConfiguration('theme');
      $this->view->colorSchemes = $application->getColorSchemes();
      $this->view->colors = $application->getColors();
  }

  /**
   * CSS action.
   */
  public function cssAction()
  {
      $application = $this->instance;
      $installer = $application->getInstaller();

      if ($this->getRequest()->isPost() && $this->_hasParam('css')) {
          if (method_exists($installer, 'setCustomCss')) {
              $installer->setCustomCss($this->_getParam('css'));
          }
          $this->_flashMessenger->addMessage( $this->translate("CSS updated") );
          return $this->_redirectToAction('css');
      }

      if (method_exists($installer, 'getCustomCss')) {
          $this->view->css = $installer->getCustomCss();
      }
  }

  /**
   * Backup action.
   */
  public function backupsAction()
  {
      // Do backup
      if ($this->getRequest()->isPost() && $this->_hasParam('dobackup')) {
          $this->instance->doBackup();
          $this->view->notification = $this->translate("Backup generated");
      }

      // Download
      if ($this->_hasParam('download')) {
          $backup = $this->_getParam('download');
          foreach ($this->instance->getBackups() as $backup) {
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
          $this->view->notification = $this->translate("Backup deleted");
      }

      // Restore
      if ($this->_hasParam('restore')) {
          $this->instance->restoreBackup( $this->_getParam('restore') );
          $this->view->notification = $this->translate("Backup restored");
      }

      $this->view->backups = $this->instance->getBackups();
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
            $this->view->notification = $this->translate("Roles updated");
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

        if (defined('LD_AJAX_USERS') && constant('LD_AJAX_USERS')) {
            uasort($users, array('Ld_Utils', "sortByOrder"));
        } else {
            ksort($users);
        }

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

            $this->_redirectToAction('configure', $instance->id);
        }
    }

    public function moveAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('path')) {
            $path = Ld_Files::cleanpath($this->_getParam('path'));
            $this->site->moveInstance($this->instance, $path);
            $this->_flashMessenger->addMessage( $this->translate("Instance moved") );
            $this->_redirectToAction('configure');
        }
    }

    protected function _redirectToAction($action = 'status', $id = null)
    {
        $url = $this->view->instanceActionUrl($action, $id);
        $this->redirectTo($url);
    }

    /* Deprecated */

    protected function _redirectTo($url) { return $this->redirectTo($url); }

    public function statusAction() { $this->_forward('configure'); }

    public function manageAction() { $this->_forward('configure'); }

}
