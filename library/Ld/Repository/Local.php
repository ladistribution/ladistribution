<?php

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
        
        $extensions = array();
        foreach ($applications as $id => $application) {
           $extensions = array_merge($extensions, $this->getPackageExtensions($id));
        }

        return array_merge($applications, $libraries, $extensions);
    }

    public function getApplications()
    {
        $dirs = Ld_Files::getDirectories($this->dir, array('lib', 'css', 'js', 'shared'));

        $applications = array();
        foreach ($dirs as $name) {
            try {
                $package = $this->getPackage($name);
            } catch (Exception $e) {
                $package = new Ld_Package();
                $package->setInfos(array('id' => $name, 'name' => $name));
            }
            $applications[$package->id] = $package;
        }
        return $applications;
    }

    public function getLibraries($type = null)
    {
        if ($type == null) {
            $libraries = array();
            foreach ($this->types['libraries'] as $type) {
                $libraries = array_merge($libraries, $this->getLibraries($type));
            }
            return $libraries;
        }

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
        $extensions = array();
        foreach ($this->getApplications() as $application) {
            $extensions = array_merge($extensions, $this->getPackageExtensions($application->id));
        }
        return $extensions;
    }

    public function getPackage($params = array())
    {
        if (is_string($params)) {
            $params = array('id' => $params);
        }

        $dir = $this->getDirectory($params);
        $manifestFile =  $dir . '/manifest.xml';
        if (!file_exists($manifestFile)) {
            throw new Exception("Can't find manifest in $dir.");
        }

        $package = new Ld_Package(array('manifest' => $manifestFile));

        $base_url = str_replace($this->getSite()->getDirectory() . '/', $this->getSite()->getUrl(), $dir);
        $package->url = $base_url . "/$package->id.zip";

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

    public function deletePackage($name)
    {
    }

    public function updatePackage($name, $params)
    {
    }

    public function importPackage($filename, $clean = true)
    {
        $package = new Ld_Package(array('zip' => $filename));

        $dir = $this->getDirectory($package);
        Ld_Files::createDirIfNotExists($dir);

        Ld_Files::copy($filename, $dir . "/$package->id.zip");
        Ld_Files::copy($filename, $dir . "/$package->id-$package->version.zip");

        Ld_Files::put($dir . "/manifest.xml", $package->getManifestXml());

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

        // remove package that are not really existing
        // applications that have extensions in this repository but that are not in this repository
        foreach ($packages as $id => $package) {
            if (empty($package->url)) {
                unset($packages[$id]);
            }
        }

        // Generate Json Index
        Ld_Files::putJson($this->dir . '/packages.json', $packages);

        // Generate HTML Index
        if (!file_exists($this->dir . '/index.html')) {
            Ld_Files::put($this->dir . '/index.html', "This is a La Distribution repository.");
        }
    }

}
