<?php

require_once 'BaseController.php';

/**
 * Packages controller
 */
class PackagesController extends BaseController
{
    public function indexAction()
    {
        $this->view->packages = $this->site->getPackages();
    }
    
    public function extensionsAction()
    {
        $type = $this->_getParam('type');
        $packageId = $this->_getParam('id');
        
        $this->view->packages = $this->site->getPackageExtensions($packageId, $type);
    }
    
    public function preferencesAction()
    {
        $type = $this->_getParam('type', 'configuration');
        $packageId = $this->_getParam('id');
        
        if ($type == 'install') {
            $this->view->preferences = $this->site->getInstallPreferences($packageId);
            return;
        }
        
        $installer = Ld_Installer_Factory::getInstaller(array('id' => $packageId));
        
        $this->view->preferences = array();
        $prefs = $installer->getPreferences($type);
        foreach ($prefs as $pref) {
            $this->view->preferences[] = $pref->toArray(); 
        }
    }

}
