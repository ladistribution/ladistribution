<?php

require_once 'BaseController.php';

class Slotter_SitesController extends Slotter_BaseController
{

    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, 'sites', 'manage')) {
            $this->_disallow();
        }
    }

    public function newAction()
    {
        if ($this->getRequest()->isPost()) {

            $config = array(
                'name'   => $this->_getParam('name'),
                'path'   => $this->_getParam('path'),
                'domain' => $this->_getParam('domain'),
                'dir'    => $this->_getParam('dir')
            );

            $subsite = new Ld_Site_Child($config);
            $subsite->setParentSite( Zend_Registry::get('site') );
            $subsite->init();

            $this->getSite()->addSite($config);

            if (!$admin = $subsite->getAdmin()) {
                $admin = $subsite->createInstance('admin', array('title' => 'Administration', 'path' => 'admin'));
            }
            $admin->setUserRoles(array($this->_getParam('administrator') => 'admin'));

            $this->_redirector->gotoSimple('index', 'index');
            return;
        }

        $preferences = $this->_getPreferences();

        $options = array();
        foreach ($this->admin->getUsers() as $id => $user) {
            $options[] = array('value' => $user['username'], 'label' => !empty($user['fullname']) ? $user['fullname'] : $user['username']) ;
        }
        $preferences[] = array('type' => 'list', 'name' => 'administrator', 'label' => 'Administrator', 'options' => $options);

        $this->view->preferences = $preferences;
    }

    public function editAction()
    {
        $id = $this->_getParam('id');

        $siteConfig = $this->site->getSite( $this->_getParam('id') );
        $subsite = new Ld_Site_Child($siteConfig);
        $subsite->setParentSite( $this->site );
        
        $this->view->configuration = $siteConfig;
        $this->view->preferences = $this->_getPreferences();

        if ($this->getRequest()->isPost()) {

          $newConfig = array(
              'name'   => $this->_getParam('name'),
              'path'   => $this->_getParam('path'),
              'domain' => $this->_getParam('domain'),
              'dir'    => $this->_getParam('dir')
          );

          if ($siteConfig['path'] != $newConfig['path']) {
              $subsite->path = $newConfig['path'];
              $subsite->setConfig('path', $newConfig['path']);
          }

          $subsite->setConfig('name', $newConfig['name']);

          if ($config['dir'] != $newConfig['dir']) {
              $subsite->dir = $newConfig['dir'];
              Ld_Files::move($siteConfig['dir'], $newConfig['dir']);
              // update all site applications
              foreach ($subsite->getApplicationsInstances() as $instance) {
                  $installer = $instance->getInstaller();
                  $installer->postMove();
              }
          }

          $this->view->configuration = $this->site->updateSite($id, $newConfig);

          $this->_redirector->gotoSimple('index', 'index');
          return;

        }
    }

    protected function _getPreferences()
    {
        $preferences = array();
        $preferences[] = array('type' => 'text', 'name' => 'name', 'label' => 'Name', 'defaultValue' => 'My New Site');
        if (defined('LD_MULTI_DOMAINS') && constant('LD_MULTI_DOMAINS')) {
            $domainPreference = array('type' => 'list', 'name' => 'domain', 'label' => 'Domain', 'options' => array());
            foreach ($this->getSite()->getDomains() as $id => $domain) {
                $domainPreference['options'][] = array(
                    'value' => $id,
                    'label' => $domain['host']
                );
                if ($domain['host'] == $this->site->getConfig('host')) {
                    $domainPreference['defaultValue'] = $id;
                }
            }
            $preferences[] = $domainPreference;
        }
        $preferences[] = array('type' => 'text', 'name' => 'path', 'label' => 'Path', 'defaultValue' => $this->getSite()->getPath() . '/new-site');
        $preferences[] = array('type' => 'text', 'name' => 'dir', 'label' => 'System Directory', 'defaultValue' => $this->getSite()->getDirectory() . '/' . 'new-site');

        return $preferences;
    }

}
