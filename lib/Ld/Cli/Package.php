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
        if (isset($this->_opts->package)) {
            $path = $this->_opts->package;
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

    public function build()
    {
        $path = $this->_getPath();
        $package = $this->_getPackage();

        $archive = $package->getId() . '.zip';

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

        foreach ($exclusions as $value) {
            $zip->addExclusion('/' . preg_quote($value, '/') . '/');
        }

        $zip->addDirectory($path . '/dist', '/dist', true);
        $zip->addExclusion('/' . preg_quote('dist', '/') . '/');
        $zip->addDirectory($path, '/application', true);

        $zip->write();
    }

    public function push()
    {
        $path = $this->_getPath();
        $package = $this->_getPackage();

        $name = empty($this->_args[1]) ? 'default' : $this->_args[1];

        $ladis = Ld_Files::getJson($path . '/.ladis');
        if (empty($ladis['repositories'][$name])) {
            throw new Exception("Repository not registered yet. Use ladis package add-remote");
        }

        extract($ladis['repositories'][$name]);

        $archive = $package->getId() . '.zip';

        $httpClient = new Zend_http_Client($url);
        $httpClient->setAuth($username, $password);
        $httpClient->setFileUpload($archive, 'file');
        $httpClient->setParameterPost('upload', 1);
        $response = $httpClient->request('POST');

        Ld_Files::rm($archive);
    }

    public function bump()
    {
        $path = $this->_getPath();

        $filename = $path . '/dist/manifest.xml';
        $manifest = new DOMDocument();
        $manifest->load($filename);
        foreach ($manifest->getElementsByTagName('version') as $element) {
            $version = explode('-', $element->nodeValue);
            $version[1] = (int)$version[1] + 1;
            $element->nodeValue = implode('-', $version);
        }
        $manifest->save($filename);
    }

    public function bbp()
    {
        $this->bump();
        $this->build();
        $this->push();
    }

    public function addRemote()
    {
        $path = $this->_getPath();
        $package = $this->_getPackage();

        if (empty($this->_args[1])) {
            $name = $this->_prompt('Remote name', 'default');
        } else {
            $name = $this->_args[1];
        }

        $url = isset($this->_opts->id) ? $this->_opts->url : $this->_prompt('Repository Push URL');
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
