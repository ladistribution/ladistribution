<?php

class Ld_Plugin_Bouncer
{

    public function infos()
    {
        return array(
            'name' => 'Bouncer',
            'url' => 'http://ladistribution.net/en/wiki/plugins/#bouncer',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.97',
            'description' => Ld_Translate::translate('Track and block agents using the Bouncer library.'),
            'license' => 'MIT / GPL'
        );
    }

    const STATUS_OK = 1;
    const STATUS_ERROR = 0;

    public function status()
    {
        if (file_exists(LD_LIB_DIR . '/Bouncer/Bouncer.php')) {
            try {
                $options = self::options();
                if ($options['backend'] == 'memcache' && !class_exists('Memcache')) {
                    return array(self::STATUS_ERROR, Ld_Translate::translate('Memcache PHP extension is needed to run this plugin.'));
                }
                require_once 'Bouncer/Bouncer.php';
                Bouncer::setOptions($options);
                Bouncer::load();
                Bouncer::getAgentsIndex();
                return array(1, sprintf(Ld_Translate::translate('%s is running.'), 'Bouncer'));
            } catch (Exception $e) {
                return array(0, $e->getMessage());
            }
        }
        return array(0, sprintf(Ld_Translate::translate('%s library not available.'), 'Bouncer'));
    }

    public function preferences()
    {
        $preferences = array();
        $preferences[] = array(
            'name' => 'bouncer_backend', 'label' => Ld_Translate::notranslate('Backend'),
            'type' => 'list', 'defaultValue' => 'redis', 'options' => array(
                array('value' => 'none', 'label' => 'None'),
                array('value' => 'memcache', 'label' => 'Memcache'),
                array('value' => 'redis', 'label' => 'Redis')
            )
        );
        $preferences[] = array(
            'type' => 'text', 'label' => Ld_Translate::notranslate('Server'),
            'name' => 'bouncer_server', 'defaultValue' => '127.0.0.1'
        );
        $preferences[] = array(
            'type' => 'text', 'label' => Ld_Translate::notranslate('Bouncer Namespace'),
            'name' => 'bouncer_id', 'defaultValue' => ''
        );
        return $preferences;
    }

    public function load()
    {
        Ld_Plugin::addAction('Site:loaded', array($this, 'bounce'));
        Ld_Plugin::addAction('Ui:beforeTopBar', array($this, 'challenge'));
    }

    public function namespaces()
    {
        $site = Zend_Registry::get('site');
        $id = $site->getConfig('bouncer_id');
        if (empty($id)) {
            $id = Ld_Auth::generatePhrase(16);
            $site->setConfig('bouncer_id', $id);
        }
        $namespaces = explode(";", $id);
        return $namespaces;
    }

    public function options()
    {
        $site = Zend_Registry::get('site');
        $backend = $site->getConfig('bouncer_backend', 'redis');
        $server = $site->getConfig('bouncer_server', '127.0.0.1');
        $servers = explode(";", $server);
        $namespaces = Ld_Plugin::applyFilters('Bouncer:namespaces', self::namespaces() ); ;
        $options = array('namespaces' => $namespaces, 'backend' => $backend, 'servers' => $servers);
        return $options;
    }

    public function bounce($site)
    {
        if (defined('LD_CLI') && constant('LD_CLI')) {
            return;
        }
        try {
            include_once 'Bouncer/Bouncer.php';
            Ld_Plugin::doAction('Bouncer:load');
            if (class_exists('Bouncer')) {
                Bouncer::setOptions( self::options() );
                Bouncer::load();
                if (!defined('LD_BOUNCER_IGNORE') || !constant('LD_BOUNCER_IGNORE')) {
                    Bouncer::bounce();
                }
            }
        } catch (Exception $e) {
        }
    }

    public function challenge()
    {
        try {
            if (class_exists('Bouncer')) {
                Bouncer::challenge();
            }
        } catch (Exception $e) {
        }
    }

}
