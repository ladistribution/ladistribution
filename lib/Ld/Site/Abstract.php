<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Site
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

abstract class Ld_Site_Abstract
{

    abstract public function getInstances();

    abstract public function getInstance($id);

    abstract public function createInstance($packageId, $preferences = array());

    abstract public function deleteInstance($instance);

    abstract public function getUsers();

    public function getPackage($id)
    {
        $packages = $this->getPackages();
        if (isset($packages[$id])) {
            $package = $packages[$id];
            $package->setSite($this);
            return $package;
        }
        throw new Exception("Unknown package '$id'");
    }

    public function hasPackage($id)
    {
        $packages = $this->getPackages();
        return isset($packages[$id]);
    }

    abstract public function getPackageExtensions($packageId, $type = null);

    public function getPackageExtension($packageId, $extensionId)
    {
        $extensions = $this->getPackageExtensions($packageId);
        if (isset($extensions[$extensionId])) {
            return $extensions[$extensionId];
        }
        throw new Exception("Unknown extension for $packageId: $extensionId");
    }

    public function isPackageInstalled($id)
    {
        $installed = $this->getLibraryInfos($id) ? true : false;
        return $installed;
    }

    // TODO: to be renamed

    public function getLibraryInfos($package)
    {
        $instances = $this->getInstances();
        foreach ($instances as $instance) {
            if ($instance['package'] == $package) {
                return $instance;
            }
        }
        return null;
    }

    abstract public function getInstallPreferences($package);

}
