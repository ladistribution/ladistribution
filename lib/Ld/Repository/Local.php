<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Repository
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Repository_Local extends Ld_Repository_Abstract
{

    protected $dir = null;

    public function __construct($params = array())
    {
        if (is_string($params)) {
            $this->id = $params;
        }

        if (is_array($params)) {
            $this->id = $params['id'];
            $this->type = $params['type'];
            $this->name = $params['name'];
        }

        $this->dir = $this->getSite()->getDirectory() . '/repositories/' . $this->name;

        Ld_Files::createDirIfNotExists($this->dir);
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function getSite()
    {
        if (isset($this->site)) {
            return $this->site;
        }
        return Zend_Registry::get('site');
    }

    public function getUrl()
    {
        return $this->getSite()->getUrl() . 'repositories/' . $this->name . '/';
    }

    public function getPackages($type = null)
    {
        $applications = $this->getApplications();

        $libraries = $this->getLibraries();

        $extensions = $this->getExtensions();

        return array_merge($applications, $libraries, $extensions);
    }

    public function getApplications()
    {
        $dirs = Ld_Files::getDirectories($this->dir, array('lib', 'css', 'js', 'shared'));

        $applications = array();
        foreach ($dirs as $name) {
            try {
                $package = $this->getPackage($name);
                $applications[$package->id] = $package;
            } catch (Exception $e) {
            }
        }
        return $applications;
    }

    public function getLibraries()
    {
        $libraries = array();
        foreach ($this->types['libraries'] as $type) {
            $libraries = array_merge($libraries, $this->_getLibrariesByType($type));
        }
        return $libraries;
    }

    protected function _getLibrariesByType($type)
    {
        $dirs = Ld_Files::getDirectories($this->dir . '/' . $type);

        $libraries = array();
        foreach ($dirs as $name) {
            $package = $this->getPackage(array('id' => $name, 'type' => $type));
            $libraries[$package->id] = $package;
        }
        return $libraries;
    }

    public function getPackageExtensions($packageId, $type = null)
    {
        if ($type == null) {
            $extensions = array();
            foreach ($this->types['extensions'] as $type) {
                $extensions = array_merge($extensions, $this->getPackageExtensions($packageId, $type));
            }
            return $extensions;
        }

        $dirs = Ld_Files::getDirectories("$this->dir/$packageId/$type");

        $extensions = array();
        foreach ($dirs as $name) {
            $extension = $this->getPackage(array('id' => $name, 'type' => $type, 'extend' => $packageId));
            $extensions[$extension->id] = $extension;
        }
        return $extensions;
    }

    public function getExtensions()
    {
        $dirs = Ld_Files::getDirectories($this->dir, array('lib', 'css', 'js', 'shared'));
        $extensions = array();
        foreach ($dirs as $id) {
            $extensions = array_merge($extensions, $this->getPackageExtensions($id));
        }
        return $extensions;
    }

    public function getPackage($params = array())
    {
        if (is_string($params)) {
            $params = array('id' => $params);
        }

        $dir = $this->getDirectory($params);
        $manifest = Ld_Manifest::loadFromDirectory($dir);
        $package =  new Ld_Package($manifest);

        $baseUrl = str_replace($this->getSite()->getDirectory() . '/', $this->getSite()->getUrl(), $dir);
        $package->url = $baseUrl . "/$package->id.zip";
        $package->setAbsoluteFilename( realpath($dir) . "/$package->id.zip" );

        return $package;
    }

    public function getReleases($name, $type)
    {
        $dir = $this->getDirectory(array('id' => $name, 'type' => $type));
        $releases = Ld_Files::getFiles($dir, 'manifest.xml');
        return $releases;
    }

    public function createPackage($name, $type = null)
    {
        $dir = $this->getDirectory(array('id' => $name, 'type' => $type));
        Ld_Files::createDirIfNotExists($dir);
    }

    public function deletePackage($packageId)
    {
        foreach ($this->getPackages() as $id => $package) {
            $dir = $this->getDirectory($package);
            if ($packageId == $id) {
                if ($package->getType() == 'application') {
                    foreach (Ld_Files::getFiles($dir) as $file) {
                        Ld_Files::unlink($dir . '/' . $file);
                    }
                } else {
                    Ld_Files::unlink($dir);
                }
                $this->generatePackageList();
                return;
            }
        }
    }

    public function updatePackage($name, $params)
    {
    }

    public function importPackage($filename, $clean = true)
    {
        $manifest = Ld_Manifest::loadFromZip($filename);
        $package = new Ld_Package($manifest);

        $dir = $this->getDirectory($package);
        Ld_Files::createDirIfNotExists($dir);

        Ld_Files::copy($filename, $dir . "/$package->id.zip");
        Ld_Files::copy($filename, $dir . "/$package->id-$package->version.zip");

        Ld_Files::put($dir . "/manifest.xml", $manifest->getRawXml());

        if ($clean) {
            Ld_Files::unlink($filename);
        }

        $this->generatePackageList();

        return $package;
    }

    public function getDirectory($params)
    {
        if (is_array($params)) {
            extract($params); // id, type, extend
        } elseif ($params instanceof Ld_Package) {
            $id = $params->id;
            $type = $params->type;
            $extend = $params->extend;
        }

        if (isset($type)) {
            if (in_array($type, $this->types['libraries'])) {
                return "$this->dir/$type/$id";
            } else if (in_array($type, $this->types['extensions'])) {
                if (empty($extend)) {
                    throw new Exception("Can't determine directory without application parameter.");
                }
                return "$this->dir/$extend/$type/$id";
            }
        }

        return $this->dir . '/' . $id;
    }

    public function generatePackageList()
    {
        $packages = $this->getPackages();

        // Generate Json Index
        Ld_Files::putJson($this->dir . '/packages.json', $packages);

        // Generate HTML Index
        if (!Ld_Files::exists($this->dir . '/index.html')) {
            Ld_Files::put($this->dir . '/index.html', 'This is a <a href="http://ladistribution.net/">La Distribution</a> repository.');
        }
    }

}
