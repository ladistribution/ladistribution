<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Cli
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Cli_Site extends Ld_Cli
{

    protected function _getArgs()
    {
        $args = parent::_getArgs();
        array_shift($args);
        return $this->_args = $args;
    }

    public function reset()
    {
        $site = $this->getSite();

        $confirm = isset($this->_opts->force) ? $this->_opts->force : $this->_confirm("Empty site?");
        if (!$confirm) {
            $this->_write("Cancelled.");
            return;
        }

        foreach ($site->getInstances() as $id => $infos) {
            if ($infos['type'] == 'application' && $infos['package'] != 'admin') {
                $instance = $site->getInstance($id);
                if ($instance) {
                    try {
                        $site->deleteInstance($instance);
                    } catch (Exception $e) {
                        $path = $instance->getPath();
                        $this->_write("Can't delete application on path '$path'.");
                        $this->_write( $e->getMessage() );
                    }
                }
            }
        }
    }

    public function cleanTmp()
    {
        $site = $this->getSite();
        Ld_Files::purgeTmpDir(0);
        $this->_write("OK.");
    }

    public function restore()
    {
        $site = $this->getSite();
        $siteDistDir = $site->getDirectory('dist');

        $tmpDir = $site->getDirectory('tmp') . '/site-restore-' . date("d-m-Y-H-i-s");
        $tmpDistDir = $tmpDir . '/dist';

        if (empty($this->_args[1]) || !Ld_Files::exists($this->_args[1])) {
            throw new Exception("No or invalid filename passed as argument.");
        }
        $backupFilename = $this->_args[1];

        Ld_Zip::extract($backupFilename, $tmpDir);

        $confirm = isset($this->_opts->force) ? $this->_opts->force : $this->_confirm("Restore this backup?");
        if (!$confirm) {
            $this->_write("Cancelled.");
            return;
        }

        // Configuration
        $config = $originalConfig = Ld_Files::getJson("$tmpDistDir/config.json");
        $config['host'] = $site->getConfig('host');
        $config['path'] = $site->getConfig('path');
        $site->setConfig($config);

        // Repositories
        $repositories = Ld_Files::getJson("$tmpDistDir/repositories.json");
        foreach ($repositories as &$repository) {
            if ($repository['type'] == 'local') {
                $repository['type'] = 'remote';
                $repository['endpoint'] = 'http://' . $originalConfig['host'] . $originalConfig['path'] . 'repositories/' . $repository['name'];
            }
        }
        $site->saveRepositoriesConfiguration($repositories);

        // Dist files
        foreach(array('colors.json', 'users.json', 'locales.json') as $file) {
            Ld_Files::copy("$tmpDistDir/$file", "$siteDistDir/$file");
            Ld_Files::copy("$tmpDistDir/$file.php", "$siteDistDir/$file.php");
        }
        Ld_Files::copy("$tmpDistDir/custom.css", "$siteDistDir/custom.css");

        // Instances
        $instances = Ld_Files::getJson("$tmpDistDir/instances.json");
        foreach ($instances as $id => $infos) {
            if ($infos['type'] == 'application') {
                $this->_write(sprintf("Restoring %s on /%s/", $infos['package'], $infos['path'] ));
                if ($instance = $site->getInstance($infos['path'])) {
                    $site->deleteInstance($instance);
                }
                $filename = "$tmpDir/$id.zip";
                try {
                    $instance = $site->cloneInstance($filename, $infos);
                    $this->_write("Success.");
                } catch (Exception $e) {
                    $this->_write("Error.");
                    $this->_write( $e->getMessage() );
                }
            }
        }

        Ld_Files::rm($tmpDir);
    }

}
