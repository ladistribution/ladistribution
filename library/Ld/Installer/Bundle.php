<?php

require_once 'Ld/Installer.php';

class Ld_Installer_Bundle extends Ld_Installer
{
    public function __construct($params = array())
    {
        parent::__construct($params);
        
        // Parsing Manifest & Instanciate Installers
        
        $this->application = (string)$this->manifest->application;
        
        // $this->application = Ld_Installer_Factory::getInstaller(array(
        //     'id' => $application,
        //     'dir' => $this->dir,
        //     'dbPrefix' => $this->dbPrefix
        // ));
        
        $this->extensions = array();
        foreach ($this->manifest->extension as $extension) {
            // $extension = (string)$extension;
            // $this->extensions[] = Ld_Installer_Factory::getInstaller(array(
            //     'id' => $extension,
            //     'dir' => $this->dir,
            //     'dbPrefix' => $this->dbPrefix
            // ));
            $this->extensions[] = (string)$extension;
        }
    }
    
    // public function install($preferences = array())
    // {
    //     $this->application->install($preferences);
    //     
    //     foreach ($this->extensions as $extension) {
    //         $extension->install($preferences);
    //     }
    // }
    
    public function getPreferences($preferences = array())
    {
        $application = Ld_Installer_Factory::getInstaller(array(
            'id' => $this->application,
            'dir' => $this->dir,
            'dbPrefix' => $this->dbPrefix
        ));
        
        $preferences = $application->getPreferences('install');
        
        foreach ($this->extensions as $extension) {
            $installer = Ld_Installer_Factory::getInstaller(array('id' => $extension));
            $preferences = array_merge($preferences, $installer->getPreferences('install'));
        }
        
        $preferences = array_merge($preferences, parent::getPreferences('install'));
        
        return $preferences;
    }
    
    // public function registerInstance($title = null, $parentId = null)
    // {
    //     $parentId = $this->application->registerInstance($title);
    //     
    //     foreach ($this->extensions as $extension) {
    //         $extension->registerInstance(null, $parentId);
    //     }
    // }
    
    // public function clean()
    // {
    //     $parentId = $this->application->clean();
    //     
    //     foreach ($this->extensions as $extension) {
    //         $extension->clean();
    //     }
    // }
}