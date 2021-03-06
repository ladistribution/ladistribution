<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Cli
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2012 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Cli_Package extends Ld_Cli
{

    protected function _getArgs()
    {
        $args = parent::_getArgs();
        array_shift($args);
        return $this->_args = $args;
    }

    protected function _getPath()
    {
        if (isset($this->_opts->path)) {
            $path = $this->_opts->path;
        } else {
            $path = getcwd();
        }

        $path = Ld_Files::real($path);

        return $path;
    }

    protected function _getPackage()
    {
        $path = $this->_getPath();
        return Ld_Package::loadFromDirectory($path);
    }

    public function about()
    {
        $path = $this->_getPath();
        $package = $this->_getPackage();

        // to be continued
    }

    public function build()
    {
        $path = $this->_getPath();
        $package = $this->_getPackage();

        $archive = $package->getId() . '.zip';

        Ld_Files::rm($archive);

        $fp = fopen($archive, 'wb');
        $zip = new fileZip($fp);

        $exclusions = array(
            $archive,
            '.htaccess',
            '.git',
            '.DS_Store',
            '.preserve',
            '.ladis',
            'build.sh',
            'dist/config.php',
            'dist/colors.json',
            'dist/configuration.json',
            'dist/roles.json',
            'dist/instance.json'
        );

        $exclusions = array_merge($exclusions, $package->getManifest()->getBuildIgnores());

        foreach ($exclusions as $value) {
            $zip->addExclusion('/' . preg_quote($value, '/') . '/');
        }

        $zip->addDirectory($path . '/dist', '/dist', true);
        $zip->addExclusion('/' . preg_quote('dist', '/') . '/');
        $zip->addDirectory($path, '/application', true);

        $zip->write();

        $this->_log("build", "package $archive successfully built");
    }

    public function push()
    {
        $path = $this->_getPath();
        $package = $this->_getPackage();

        $name = empty($this->_args[1]) ? 'default' : $this->_args[1];

        $archive = empty($this->_args[2]) ? $package->getId() . '.zip' : $this->_args[2];

        $ladis = Ld_Files::getJson($path . '/.ladis');
        if (empty($ladis['repositories'][$name])) {
            throw new Exception("Repository not registered yet. Use 'ladis package add-remote' to register a repository.");
        }

        extract($ladis['repositories'][$name]);

        $httpClient = new Zend_http_Client($url);
        $httpClient->setConfig(array('useragent' => 'ladis (La Distribution CLI)'));
        $httpClient->setAuth($username, $password);
        $httpClient->setFileUpload($archive, 'file');
        $httpClient->setParameterPost('upload', 1);
        $response = $httpClient->request('POST');

        $this->_log("push", "package successfully pushed to $url");
    }

    public function bump()
    {
        $path = $this->_getPath();

        $filename = $path . '/dist/manifest.xml';
        if (!Ld_Files::exists($filename)) {
            $alternateFilename = $path . '/manifest.xml';
            if (Ld_Files::exists($alternateFilename)) {
                $filename = $alternateFilename;
            } else {
                throw new Exception("manifest.xml doesn't exists or is unreadable in $path");
            }
        }

        $manifest = new DOMDocument();
        $manifest->load($filename);
        foreach ($manifest->getElementsByTagName('version') as $element) {
            $version = explode('-', $element->nodeValue);
            $version[1] = (int)$version[1] + 1;
            $element->nodeValue = $version = implode('-', $version);
        }
        $manifest->save($filename);

        $this->_log("bump", "package bumped to version $version");
    }

    public function bbp()
    {
        $this->bump();
        $this->build();
        $this->push();
    }

    public function release()
    {
        $this->bbp();
    }

    public function addRemote()
    {
        $path = $this->_getPath();

        if (empty($this->_args[1])) {
            $name = $this->_prompt('Remote name', 'default');
        } else {
            $name = $this->_args[1];
        }

        if (empty($this->_args[2])) {
            $url = $this->_prompt('Repository Push URL');
        } else {
            $url = $this->_args[2];
        }

        /* To be replaced by Api Key */
        $username = isset($this->_opts->username) ? $this->_opts->username : $this->_prompt('Username');
        $password = isset($this->_opts->password) ? $this->_opts->password : $this->_prompt('Password');

        $ladis = Ld_Files::getJson($path . '/.ladis');
        if (empty($ladis)) {
            $ladis = array();
        }
        if (empty($ladis['repositories'])) {
            $ladis['repositories'] = array();
        }
        $ladis['repositories'][$name] = compact('url', 'username', 'password');
        Ld_Files::putJson($path . '/.ladis', $ladis, false);
    }

}
