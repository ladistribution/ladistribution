<?php

class Ld_Installer_Spip extends Ld_Installer
{

    function install($preferences = array())
    {
        parent::install($preferences);

        $this->dbPrefix = str_replace('_', '', $this->dbPrefix);
    }

    function postInstall($preferences = array())
    {
        $this->performHttpInstall($preferences);

        $this->createConnectFile();
    }

    function performHttpInstall($preferences = array())
    {
        $databases = $this->getSite()->getDatabases();
        $db = $databases[ $this->getInstance()->getDb() ];

        $this->httpClient = new Zend_Http_Client();
        $this->httpClient->setCookieJar();
        $this->httpClient->setUri($this->instance->getUrl() . 'ecrire/?exec=install');

        $response = $this->httpClient->request('GET');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }

        $this->httpClient->setParameterPost(array(
          'exec'  => 'install',
          'etape' => 'chmod'
        ));
        $response = $this->httpClient->request('POST');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }

        $this->httpClient->setUri($this->instance->getUrl() . 'ecrire/?exec=install&etape=1&chmod=493');
        $response = $this->httpClient->request('GET');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }

        $this->httpClient->setUri($this->instance->getUrl() . 'ecrire/?exec=install');

        $this->httpClient->setParameterPost(array(
            'exec'       => 'install',
            'etape'      => '2',
            'chmod'      => '0755',
            'server_db'  => 'mysql',
            'adresse_db' => $db['host'],
            'login_db'   => $db['user'],
            'pass_db'    => $db['password']
        ));
        $response = $this->httpClient->request('POST');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }

        $this->httpClient->setParameterPost(array(
          'exec' => 'install',
          'etape' => '3',
          'server_db' => 'mysql',
          'adresse_db' => $db['host'],
          'login_db'   => $db['user'],
          'pass_db'    => $db['password'],
          'choix_db'   => $db['name'],
          'table_new'  => 'spip', // useless but ...
          'tprefix'    => $this->dbPrefix
        ));
        $response = $this->httpClient->request('POST');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }

        $this->httpClient->setParameterPost(array(
          'exec' => 'install',
          'etape' => '4',
          'server_db' => 'mysql',
          'adresse_db' => $db['host'],
          'login_db'   => $db['user'],
          'pass_db'    => $db['password'],
          'sel_db'     => $db['name'],
          'nom'        => $preferences['admin_fullname'],
          'email'      => $preferences['admin_email'],
          'login'      => $preferences['admin_username'],
          'pass'       => $preferences['admin_password'],
          'pass_verif' => $preferences['admin_password']
        ));
        $response = $this->httpClient->request('POST');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }

        $this->httpClient->setParameterPost(array(
          'exec'  => 'install',
          'etape' => 'fin'
        ));
        $response = $this->httpClient->request('POST');
        if (constant('LD_DEBUG')) {
            echo $response->getBody();
        }
    }

    public function createConnectFile()
    {
        Ld_Files::copy($this->getDir() . 'config/connect.php', $this->getAbsolutePath() . '/config/connect.php');
    }

}
