<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Cli
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Cli
{

    public $_opts = null;

    public $_action = null;

    public $_filterFromClientNaming = null;

    public function __construct()
    {
        $this->_opts = new Zend_Console_Getopt(array(
            'site|s-s' => 'a path to a ladistribution intialised site',
            'force|f' => 'force command'
        ));

        $this->_args = $this->_opts->getRemainingArgs();

        $this->_action = isset($this->_args[0]) ? $this->_args[0] : 'about';
    }

    public function dispatch()
    {
        $method = $this->convertFromClientNaming($this->_action);

        if (strtolower($method) == 'clone') {
            $method = 'duplicate';
        }

        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            throw new Exception("Unknown command $method.");
        }
    }

    protected function _prompt($label)
    {
        $this->_write(sprintf("    %s:", $label), false);
        return trim(fgets(STDIN));
    }

    protected function _confirm($label, $default = 'y')
    {
        if ($default == 'y') {
            $this->_write(sprintf("ladis: %s [Y/n] ", $label), false);
            return strtolower(trim(fgets(STDIN))) != 'n';
        } else {
            $this->_write(sprintf("ladis: %s [y/N] ", $label), false);
            return strtolower(trim(fgets(STDIN))) != 'y';
        }
    }

    protected function _write($msg, $linebreak = true)
    {
        fwrite(STDOUT, $msg);
        if ($linebreak) {
            fwrite(STDOUT, PHP_EOL);
        }
    }

    protected function _log($action, $message)
    {
        $this->_write("# $action: $message");
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function convertFromClientNaming($string)
    {
        if (!$this->_filterFromClientNaming) {
            require_once 'Zend/Filter/Word/DashToCamelCase.php';
            $this->_filterFromClientNaming = new Zend_Filter_Word_DashToCamelCase();
        }

        return $this->_filterFromClientNaming->filter($string);
    }

    public function getSite()
    {
        if (isset($this->_site)) {
            return $this->_site;
        }

        $configs = array();

        if (isset($this->_opts->site)) {
            $configs[] = realpath($this->_opts->site) . '/dist/site.php';
        }

        $configs[] = getcwd() . '/dist/site.php';
        $configs[] = getcwd() . '/dist/config.php';

        foreach ($configs as $config) {
            if (file_exists($config)) {
                require_once($config);
                $this->_log('config', $config);
                return $this->_site = Zend_Registry::get('site');
            }
        }

        throw new Exception('Site not found or not initialized.');
    }

    protected function _getRepository($name)
    {
        $site = $this->getSite();
        $repositories = $site->getRepositories();
        foreach ($repositories as $repository) {
            if ($repository->name == $name) {
                return $repository;
            }
        }
        if (empty($repository)) {
            throw new Exception("Unknown repository");
        }
    }

    // General

    public function init()
    {
        Ld_Loader::defineConstants(LD_DIR);

        if (!empty($this->_args[1])) {
            if (!file_exists($this->_args[1])) {
                throw new Exception("Non existing directory passed as argument.");
            }
            $dir = $this->_args[1];
        } else {
            $dir = getcwd();
        }

        $dir = '/' . Ld_Files::cleanpath($dir);
        $this->_log('dir', $dir);

        $host = $this->_prompt('host');
        $path = '/' . Ld_Files::cleanpath($this->_prompt('path'));

        $site = new Ld_Site_Local(array('dir' => $dir, 'host' => $host, 'path' => $path));
        $site->init();
    }

    public function siteUpdate()
    {
        $site = $this->getSite();

        $this->_write(sprintf("The following packages have an update:"));

        $updates = array();

        foreach ($site->getInstances() as $id => $infos) {
            // Applications
            if ($infos['type'] == 'application') {
                $instance = $site->getInstance($id);
                if ($instance && $version = $instance->hasUpdate()) {
                    $updates[] = $instance;
                    $this->_write(sprintf(" - '%s' instance at /%s/ (%s => %s)",
                        $instance->getPackageId(), $instance->getPath(), $instance->getVersion(), $version));
                    // Extensions
                    foreach ($instance->getExtensions() as $extension) {
                        if ($extension && $version = $extension->hasUpdate()) {
                            $updates[] = $extension;
                            $this->_write(sprintf("     > '%s' extension (%s => %s)",
                                $extension->getPackageId(), $extension->getVersion(), $version));
                        }
                    }
                }
            // Libraries
            } else {
                $instance = new Ld_Instance_Library($infos);
                $instance->setSite($site);
                if ($instance && $version = $instance->hasUpdate()) {
                    $updates[] = $infos['package'];
                    $this->_write(sprintf(" - '%s' library (%s => %s)",
                        $instance->getPackageId(), $instance->getVersion(), $version));
                }
            }
        }

        if (empty($updates)) {
            $this->_write(sprintf(" No package. Everything is up to date."));
            return;
        }

        $confirm = isset($this->_opts->force) ? $this->_opts->force : $this->_confirm("Update this packages?");
        if ($confirm) {
            foreach ($updates as $update) {
                if ($update instanceof Ld_Instance_Extension) {
                    $update->parent()->updateExtension($update);
                } else {
                    $site->updateInstance($update);
                }
            }
            $this->_write("Update OK.");
        }
    }

    public function instances()
    {
        foreach ($this->getSite()->getInstances('application') as $id => $instance) {
            $this->_write(sprintf("%s\t%s\t%s\t%s\t%s",
                $id, $instance['type'], $instance['package'], $instance['name'], $instance['path']));
        }
    }

    public function about()
    {
        $this->getSite();
        $this->_write("La Distribution '" . LD_RELEASE . "'");
        $this->_write("Copyright (c) 2009 h6e.net");
        $this->_write("Licensed under the GPL and MIT licences.");
    }

    // Users

    public function users()
    {
        foreach ($this->getSite()->getUsers() as $id => $user) {
            $this->_write(sprintf("%s", $user['username']));
        }
    }

    public function addUser()
    {
        $username   = isset($this->_args[1]) ? $this->_args[1] : $this->_prompt('Username');
        $password   = isset($this->_args[2]) ? $this->_args[2] : $this->_prompt('Password');
        $fullname   = isset($this->_opts->fullname) ? $this->_opts->fullname : $this->_prompt('Full Name');
        $email      = isset($this->_opts->email)    ? $this->_opts->email    : $this->_prompt('Email');
        $this->getSite()->addUser(compact('fullname', 'email', 'username', 'password'));
    }

    public function deleteUser()
    {
        $username   = isset($this->_args[1]) ? $this->_args[1] : $this->_prompt('Username');
        $confirm    = isset($this->_opts->force) ? $this->_opts->force : $this->_confirm("Delete $username?");
        if ($confirm) {
            $this->getSite()->deleteUser($username);
        }
    }

    // Databases

    public function databases()
    {
        $databases = $this->getSite()->getDatabases();
        if (empty($databases)) {
            $this->_write('None');
        } else {
            foreach ($this->getSite()->getDatabases() as $id => $db) {
                $this->_write(sprintf("$id => %s://%s@%s/%s", $db['type'], $db['user'], $db['host'], $db['name']));
            }
        }
    }

    public function addDatabase()
    {
        $type = 'mysql';
        $host = isset($this->_opts->host) ? $this->_opts->host : $this->_prompt('Host');
        $name = isset($this->_opts->name) ? $this->_opts->name : $this->_prompt('Name');
        $user = isset($this->_opts->user) ? $this->_opts->user : $this->_prompt('User');
        $password = isset($this->_opts->password) ? $this->_opts->password : $this->_prompt('Password');
        $this->getSite()->addDatabase(compact('type', 'host', 'name', 'user', 'password'));
    }

    public function deleteDatabase()
    {
        if (empty($this->_args[1])) {
            throw new Exception("No database ID passed as argument.");
        }
        $id = $this->_args[1];
        $confirm    = isset($this->_opts->force) ? $this->_opts->force : $this->_confirm("Delete database connection $id?");
        if ($confirm) {
            $this->getSite()->deleteDatabase($id);
            $this->_write("Database connection deleted.");
        }
    }

    // Instances

    public function install()
    {
        if (empty($this->_args[1])) {
            throw new Exception("No package ID passed as argument.");
        }
        $packageId = $this->_args[1];

        $preferences = array();
        foreach ($this->getSite()->getInstallPreferences($packageId) as $pref) {
            switch ($pref['type']) {
                case 'hidden':
                    $preferences[ $pref['name'] ] = $pref['defaultValue'];
                    break;
                case 'list':
                    if (isset($pref['options'][0]['value'])) {
                        $pref['defaultValue'] = $pref['options'][0]['value'];
                    }
                default:
                    if (isset($pref['defaultValue'])) {
                        fwrite(STDOUT, sprintf("    %s [%s]:", $pref['label'], $pref['defaultValue']));
                        $value = trim( fgets(STDIN) );
                        if (empty($value)) {
                            $value = $pref['defaultValue'];
                        }
                        $preferences[ $pref['name'] ] = $value;
                    } else {
                        fwrite(STDOUT, sprintf("    %s: ", $pref['label']));
                        $preferences[ $pref['name'] ] = trim( fgets(STDIN) );
                    }
            }
        }
        $result = $this->getSite()->createInstance($packageId, $preferences);
        $this->_write(sprintf("%s v%s successfully installed on %s",
            $result->getPackageId(), $result->getVersion(), $result->getPath() ));
    }

    protected function getInstance()
    {
        if (empty($this->_args[1])) {
            $path = getcwd();
        } else {
            $path = $this->_args[1];
        }

        $instance = $this->getSite()->getInstance($path);
        if (empty($instance)) {
            throw new Exception("No valid instance found at $path.");
        }

        return $instance;
    }

    public function status()
    {
        $instance = $this->getInstance();

        $infos = $instance->getInfos();
        $infos['relativePath'] = $infos['path'];
        $infos['absolutePath'] = realpath($this->getSite()->getDirectory() . '/' . $infos['path']);
        foreach (array('name', 'type', 'package', 'version', 'relativePath', 'absolutePath', 'url') as $k) {
            $this->_write(sprintf("    [$k] -> %s", $infos[$k]));
        }
    }

    public function update()
    {
        $instance = $this->getInstance();

        $result = $this->getSite()->updateInstance($instance);
        if ($result) {
            $this->_write(sprintf("Update OK at /%s/. %s now at version %s",
                $result->getPath(), $result->getPackageId(), $result->getVersion()));
        }
    }

    public function delete()
    {
        $instance = $this->getInstance();

        $confirm = isset($this->_opts->force) ? $this->_opts->force :
            $this->_confirm(sprintf("Delete '%s'?", $instance->getName()), 'y');
        if ($confirm) {
            $result = $this->getSite()->deleteInstance($instance);
            $this->_write("Instance deleted.");
        }
    }

    public function duplicate()
    {
        if (empty($this->_args[1]) || !file_exists($this->_args[1])) {
            throw new Exception("No or invalid filename passed as argument.");
        }
        $filename = $this->_args[1];

        $preferences = array();
        $preferences['path'] = $this->_prompt('Path');

        $instance = $this->getSite()->cloneInstance($filename, $preferences);
    }

    // Repositories

    public function importPackage()
    {
        if (empty($this->_args[1])) {
            throw new Exception("No or invalid repository passed as argument.");
        }
        $repository = $this->_getRepository($this->_args[1]);

        if (empty($this->_args[2]) || !file_exists($this->_args[2])) {
            throw new Exception("No or invalid filename passed as argument.");
        }
        $filename = $this->_args[2];

        $package = $repository->importPackage($filename, false);
        $this->_write(sprintf("%s v%s successfully imported in '%s' repository",
            $package->id, $package->version, $repository->name));
    }

}
