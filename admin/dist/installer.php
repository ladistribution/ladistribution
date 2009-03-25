<?php

class Ld_Installer_Admin extends Ld_Installer
{

    public function install($preferences)
    {
        // Deploy the files
        parent::install($preferences);

        $this->postConfigure();
    }

    public function setConfiguration($configuration = array(), $type = 'general')
    {
        $configuration = array_merge($this->getConfiguration(), $configuration);
        file_put_contents($this->absolutePath . "/dist/configuration.json", Zend_Json::encode($configuration));
        $this->postConfigure();
        return $configuration;
    }

    public function postConfigure()
    {
        $htaccess = '';
        if (true === LD_REWRITE) {
            $path = LD_BASE_PATH . '/' . $this->path . '/';
            $htaccess .= "RewriteEngine on\n";
            $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
            $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
            $htaccess .= "RewriteBase $path\n";
            $htaccess .= "RewriteRule !\.(js|ico|txt|gif|jpg|png|css)$ index.php\n";
        }
        file_put_contents($this->absolutePath . "/.htaccess", $htaccess);
    }

    public function getConfiguration()
    {
        if (file_exists($this->absolutePath . "/dist/configuration.json")) {
            $json = file_get_contents($this->absolutePath . "/dist/configuration.json");
            return Zend_Json::decode($json);
        }
        return array();
    }

}
