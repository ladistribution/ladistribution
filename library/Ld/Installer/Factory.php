<?php

// require_once 'Ld/Packages.php';
require_once 'Ld/Installer.php';

class Ld_Installer_Factory
{
    public static $id = null;
    public static $dir = null;
    public static $package = null;
    public static $instance = null;
    
    public static function getPackage()
    {
        $httpClient = new Zend_Http_Client();
        $httpClient->setUri(self::$package->url);
        $response = $httpClient->request();
        if ($response->isError()) {
            throw new Exception(
                'HTTP Error with ' . self::$package->url . ' - ' . $response->getStatus() . ' : ' . $response->getMessage()
            );
        }
        $file = $response->getBody();
        return $file;
    }
    
    public static function getManifest()
    {
        if (isset(self::$instance)) {
            self::$dir = $dir = self::$instance->getAbsolutePath() . '/';
        } else {
            $filename = LD_TMP_DIR . '/' . self::$package->id . '-' . self::$package->version . '.zip';
            if (!file_exists($filename)) {
                $file = self::getPackage();
                file_put_contents($filename, $file);
            }
            self::$dir = $dir = LD_TMP_DIR . '/' . self::$package->id . '-' . self::$package->version . '/';
            if (!file_exists($dir)) {
                $uz = new fileUnzip($filename);
                $uz->unzipAll($dir);
            }
        }
        
        $filename = $dir . '/dist/manifest.xml';
        if (!file_exists($filename)) {
            $filename = $dir . '/manifest.xml'; // alternate name
        }
        if (file_exists($filename)) {
            $manifestXml = file_get_contents($filename);
        } else {
            throw new Exception("manifest.xml doesn't exists or is unreadable in $dir");
        }

        try {
            $manifest = new SimpleXMLElement($manifestXml);
        } catch (Exception $e) {
            throw new Exception("Can't parse $filename as XML.");
        }

        return $manifest;
    }
    
    public static function getInstaller($params = array())
    {
        if (isset($params['id'])) {
            throw new Exception('getInstallerById is no more supported.');
        } elseif (isset($params['instance'])) {
            self::$instance = $params['instance'];
            self::$id = self::$instance->getPackageId();
        } elseif (isset($params['package'])) {
            self::$package = $params['package'];
            self::$id = self::$package->id;
        }

        $dbPrefix = isset($params['dbPrefix']) ? $params['dbPrefix'] : null;
        
        $manifest = self::getManifest();
        
        switch ( (string)$manifest->type ) {
            
            case 'bundle':
                return new Ld_Installer_Bundle(array('id' => self::$id, 'dir' => self::$dir));
            
            default:
            
                $className = (string)$manifest->installer['name'];
                $classFile = (string)$manifest->installer['src'];
                
                if (empty($classFile)) {
                    $className = 'Ld_Installer';
                } else {
                    // This may be problematic
                    if (!class_exists($className, false)) {
                        require_once self::$dir . $classFile;
                    }
                }
                
                return new $className(array(
                    'id' => self::$id,
                    'dir' => self::$dir,
                    'instance' => self::$instance,
                    'dbPrefix' => $dbPrefix
                ));
        }
    }
}
