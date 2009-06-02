<?php

class Ld_Loader
{

    public static function defineConstants($dir)
    {
        error_reporting( E_ALL /* | E_NOTICE | E_STRICT */ );

        date_default_timezone_set('UTC');

        defined('LD_DEBUG') OR define('LD_DEBUG', true);

        defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.h6e.net/');

        defined('LD_REWRITE') OR define('LD_REWRITE', true);

        defined('LD_TMP_DIR') OR define('LD_TMP_DIR', realpath( $dir ) . '/tmp');

        defined('LD_LIB_DIR') or define('LD_LIB_DIR', realpath( $dir ) . '/lib' );

        set_include_path( get_include_path() . PATH_SEPARATOR . LD_LIB_DIR );
    }
    
    public static function loadSite($dir)
    {
        self::defineConstants($dir);

        // Zend Framework & Ld Libraries
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Ld_');

        // TODO: to be replaced by an Autoloader
        require_once 'clearbricks/common/lib.files.php';
        require_once 'clearbricks/zip/class.unzip.php';

        Zend_Session::start();

        // Site configuration
        $configFilename = $dir . '/dist/config.json';
        if (file_exists($configFilename)) {
            $config = Zend_Json::decode(file_get_contents($configFilename));
        } else {
            $config = array();
        }
        if (empty($config['dir'])) {
            $config['dir'] = $dir;
        }
        if (empty($config['host']) && isset($_SERVER['SERVER_NAME'])) {
            $config['host'] = $_SERVER['SERVER_NAME'];
        }

        // Site object
        $site = new Ld_Site_Local($config);
        Zend_Registry::set('site', $site);

        if (function_exists('mcrypt_ecb')) {
            $cookieManager = new Ld_Cookie('SECRET_KEY', array('cookiePath' => $site->getPath()));
            $authStorage = new Ld_Auth_Storage_Cookie($cookieManager);
        } else {
            $path = $site->getPath();
            $namespace = empty($path) ? 'default' : $path;
            $authStorage = new Zend_Auth_Storage_Session($namespace);
        }
        Zend_Registry::set('authStorage', $authStorage);
        
        // Legacy CSS Constants
        defined('LD_CSS_URL') OR define('LD_CSS_URL', $site->getUrl('css'));
        defined('H6E_CSS') OR define('H6E_CSS', LD_CSS_URL);

        return $site;
    }

}
