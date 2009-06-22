<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Installer
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

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
                Ld_Files::put($filename, $file);
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
            $manifestXml = Ld_Files::get($filename);
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
            self::$package = null;
        } elseif (isset($params['package'])) {
            self::$package = $params['package'];
            self::$id = self::$package->id;
            self::$instance = null;
        }

        $dbPrefix = isset($params['dbPrefix']) ? $params['dbPrefix'] : null;
        
        $manifest = self::getManifest();

        // Reading or detecting installer class/file 
        if (isset($manifest->installer)) {
            $className = (string)$manifest->installer['name'];
            $classFile = (string)$manifest->installer['src'];
        } elseif (file_exists(self::$dir . 'dist/installer.php')) {
            $classFile = 'dist/installer.php';
            $className = 'Ld_Installer_' . ucfirst(self::$id);
        }

        if (empty($classFile)) {
            // Defaults
            if ((string)$manifest->type == 'bundle') {
                $className = 'Ld_Installer_Bundle';
            } else {
                $className = 'Ld_Installer';
            }
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
