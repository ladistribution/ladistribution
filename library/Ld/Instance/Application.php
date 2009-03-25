<?php

class Ld_Instance_Application extends Ld_Instance
{
    public function getInfos()
    {
        parent::getInfos();
        
        if (empty($this->infos['path'])) {
            throw new Exception('Path empty. Is it an application ?');
        }
        
        $this->infos['url'] = LD_BASE_URL . $this->path . '/';
        
        return $this->infos;
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function setPath($path)
    {
        $this->path = $path;
        $this->absolutePath = LD_ROOT . '/' . $path;
        $this->instanceJson = $this->absolutePath . '/dist/instance.json';
    }
    
    // public function update()
    // {
    //     $package = $this->site->getPackage($this->package);
    //     
    //     // Update
    //     $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));
    //     $installer->setPath($this->path);
    //     $installer->update();
    //     
    //     // Update registry
    //     $this->setInfos(array('version' => $package->version))->save();
    // }

    public function getExtensions()
    {
        $extensions = array();
        if (isset($this->infos['extensions']) && count($this->infos['extensions']) > 0) {
            foreach ($this->infos['extensions'] as $key => $extension) {
                try {
                    $extensionInstance = new Ld_Instance_Extension($this->infos['path'] . '/' . $extension['path']);
                    // we can do better i'm sure
                    // if (isset($this->site)) {
                    //           $extensionInstance->setSite($this->site);
                    //       }
                    $extensions[$key] = $extensionInstance;
                } catch(Exception $e) {
                    // TODO: log this
                    // unset($this->infos['extensions'][$key]);
                }
            }
        }
        return $extensions;
    }
    
    public function getExtension($path)
    {
        return new Ld_Instance_Extension($this->path . '/' . $path);
    }
    
    public function updateExtension($extension)
    {
        if (is_string($extension)) {
            $extension = $this->getExtension($extension);
        }
        
        $package = $this->site->getPackageExtension($this->package, $extension->package);
        
        // Update
        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));
        $installer->setPath( $this->path . '/' . $extension->path );
        $installer->update();
        
        // Update registry
        $extension->setInfos(array('version' => $package->version))->save();
    }

    
    public function removeExtension($extension)
    {
        if (is_string($extension)) {
            $extension = $this->getExtension($extension);
        }
        
        // Uninstall
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $extension));
        $installer->setPath( $this->path . '/' . $extension->path );
        $installer->uninstall();
        
        // Unregister
        $this->unregisterExtension($extension->path);
    }
    
    public function getPreferences($type = 'preferences')
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $this));
        
        $preferences = array();
        
        $prefs = $installer->getPreferences($type);
        foreach ($prefs as $pref) {
            $preferences[] = is_object($pref) ? $pref->toArray() : $pref;
        }
        
        return $preferences;
    }
    
    public function getConfiguration($type = 'general')
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $this));
        return $installer->getConfiguration($type);
    }
    
    public function setConfiguration($configuration, $type = 'general')
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $this));
        return $installer->setConfiguration($configuration, $type);
    }
        
    public function registerExtension($params)
    {
        if (empty($this->infos['extensions'])) {
            $this->infos['extensions'] = array();
        }
        $this->infos['extensions'][] = $params;
        $this->save();
    }
    
    public function unregisterExtension($path)
    {
        foreach($this->infos['extensions'] as $key => $extension) {
            if ($extension['path'] == $path) {
                $found = true;
                unset($this->infos['extensions'][$key]);
            }
        }
        if (isset($found) && $found) {
            $this->save();
            // return;
        }
        // throw new Exception("Can't unregister extension '$path', not found.");
    }
}