<?php

require_once 'BaseController.php';

/**
 * Packages controller
 */
class PackagesController extends BaseController
{
    
    public function preferencesAction()
    {
        $type = $this->_getParam('type', 'configuration');
        $packageId = $this->_getParam('id');
        
        $installer = Ld_Installer_Factory::getInstaller(array('id' => $packageId));
        
        $this->view->preferences = array();
        $prefs = $installer->getPreferences($type);
        foreach ($prefs as $pref) {
            $this->view->preferences[] = $pref->toArray(); 
        }
    }
    
}
