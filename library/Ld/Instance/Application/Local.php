<?php

class Ld_Instance_Application_Local extends Ld_Instance_Application_Abstract
{

    protected $absolutePath;

    protected $instanceJson;

    public function getInfos()
    {
        if (empty($this->infos)) {

            if (!file_exists($this->getAbsolutePath())) {
                throw new Exception("no application found in path $this->absolutePath");
            }

            if (!file_exists($this->getInstanceJson())) {
                throw new Exception("instance.json not found in path $this->absolutePath");
            }

            $json = file_get_contents($this->getInstanceJson());
            $this->setInfos(Zend_json::decode($json));

            if (empty($this->infos['path'])) {
                throw new Exception('Path empty. Is it an application?');
            }

            $this->infos['url'] = $this->url = LD_BASE_URL . $this->path . '/';

        }

        return $this->infos;
    }

    public function save()
    {
        $json = Zend_Json::encode($this->infos);
        file_put_contents($this->getInstancesJson(), $json);
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function setPath($path)
    {
        $this->path = $path;
        $this->absolutePath = LD_ROOT . '/' . $path;
    }

    public function getAbsolutePath()
    {
        return $this->absolutePath = LD_ROOT . '/' . $this->path;
    }
    
    public function getInstanceJson()
    {
        return $this->getAbsolutePath() . '/dist/instance.json';
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getDbPrefix()
    {
        return $this->db_prefix;
    }

    /* From Installer */

    public function getInstaller()
    {
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $this));
        return $installer;
    }

    public function getLinks()
    {
        $manifest = $this->getInstaller()->getManifest();
        $links = array();
        foreach ($manifest->link as $link) {
            $rel = $link['rel'];
            $href = LD_BASE_URL . $this->path . $link['href'];
            $links[] = compact('rel', 'href');
        }
        return $links;
    }

    public function getPreferences($type = 'preferences')
    {
        $preferences = array();
        
        $prefs = $this->getInstaller()->getPreferences($type);
        foreach ($prefs as $pref) {
            $preferences[] = is_object($pref) ? $pref->toArray() : $pref;
        }
        
        return $preferences;
    }

    public function getThemes()
    {
        return $this->getInstaller()->getThemes();
    }

    public function setTheme($theme)
    {
        return $this->getInstaller()->setTheme($theme);
    }
    
    public function getConfiguration($type = 'general')
    {
        return $this->getInstaller()->getConfiguration($type);
    }

    public function setConfiguration($configuration, $type = 'general')
    {
        return $this->getInstaller()->setConfiguration($configuration, $type);
    }

    
    
    public function getExtensions()
    {
        if (isset($this->extensions)) {
            return $this->extensions;
        }

        $this->getInfos(); // should be temporary

        $extensions = array();
        if (isset($this->infos['extensions']) && count($this->infos['extensions']) > 0) {
            foreach ($this->infos['extensions'] as $key => $extension) {
                // print_r($extension);
                // try {
                    $instance = new Ld_Instance_Extension($extension);
                    $instance->setParent($this);
                    // $extensionInstance->setPath($this->infos['path'] . '/' . $extension['path']);
                    // we can do better i'm sure
                    // if (isset($this->site)) {
                    //           $extensionInstance->setSite($this->site);
                    //       }
                    $extensions[$key] = $instance;
                // } catch(Exception $e) {
                    // TODO: log this
                //    echo $e->getMessage();
                    // unset($this->infos['extensions'][$key]);
                //}
            }
        }
        // print_r($extensions);
        return $this->extensions = $extensions;
    }

    public function getExtension($path)
    {
        foreach ($this->getExtensions() as $extension) {
            if ($path == $extension->getPath()) {
                 return $extension;
            }
        }
        return null;
    }

    public function addExtension($extension, $preferences = array())
    {
        $package = $this->site->getPackageExtension($this->getPackageId(), $extension);

        // Install

        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));

        foreach ($installer->getDependencies() as $dependency) {
            if (null === $this->site->_getLibraryInfos($dependency)) {
                $this->site->createInstance($dependency);
            }
        }

        $extendedPath = $installer->getExtendedPath();
        if (empty($extendedPath)) {
            throw new Exception('Extended path not defined.');
        } else {
            $installer->setPath( $this->getPath() . '/' . $extendedPath );
        }

        $installer->install($preferences);

        // Register

        $params = array(
            'package'   => $package->id,
            'type'      => $package->type,
            'version'   => $package->version,
            'path'      => $extendedPath
        );

        // $extensionInstance = new Ld_Instance_Extension($instance->path . '/' . $params['path']);
        // $extensionInstance->setInfos($params)->save();

        $this->registerExtension($params);
    }
        
    public function updateExtension($extension)
    {
        if (is_string($extension)) {
            $extension = $this->getExtension($extension);
        }
        
        $package = $this->site->getPackageExtension($this->getPackageId(), $extension->package);
        
        // Update
        $installer = Ld_Installer_Factory::getInstaller(array('package' => $package));
        // $installer->setPath( $this->path . '/' . $extension->path );
        $installer->update();
        
        // Update registry
        // $extension->setInfos(array('version' => $package->version))->save();
        
        // We update application registry instead
        foreach ($this->infos['extensions'] as $key => $extension) {
            if ($extension['path'] == $path) {
                $this->infos['extensions'][$key]['version'] = $package->version;
                $this->save();
                break;
            }
        }
    }

    public function removeExtension($extension)
    {
        if (is_string($extension)) {
            $extension = $this->getExtension($extension);
        }
        
        // Uninstall
        $installer = Ld_Installer_Factory::getInstaller(array('instance' => $extension));
        $installer->uninstall();

        // Unregister
        $this->unregisterExtension( $extension->getPath() );
    }

    public function registerExtension($params)
    {
        if (empty($this->infos['extensions'])) {
            $this->infos['extensions'] = array();
        }
        // $key = $params['path'];
        $this->infos['extensions'][] = $params;
        $this->save();
    }

    public function unregisterExtension($path)
    {
        $this->getInfos(); // should be temporary

        foreach ($this->infos['extensions'] as $key => $extension) {
            if ($extension['path'] == $path) {
                $found = true;
                unset($this->infos['extensions'][$key]);
            }
        }
        if (isset($found) && $found) {
            $this->save();
        }
    }

}
