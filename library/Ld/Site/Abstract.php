<?php

abstract class Ld_Site_Abstract
{

    abstract public function getInstances();

    abstract public function getInstance($id);

    abstract public function createInstance($packageId, $preferences = array());

    abstract public function deleteInstance($instance);

    abstract public function getUsers();

    abstract public function getPackages();

    public function getPackage($id)
    {
        $packages = $this->getPackages();
        if (isset($packages[$id])) {
            return $packages[$id];
        }
        throw new Exception("Unknown package: $id");
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

    // TODO: to be renamed
    public function _getLibraryInfos($package)
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
