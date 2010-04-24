<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Loader
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Loader
{

    public static $site = null;

    public static $config = array();

    public static $constantsDefined = false;

    public static $autoloadRegistered = false;

    public static function defineConstants($dir)
    {
        date_default_timezone_set('UTC');

        defined('LD_DEBUG') OR define('LD_DEBUG', false);

        defined('LD_REWRITE') OR define('LD_REWRITE', true);

        defined('LD_TMP_DIR') OR define('LD_TMP_DIR', realpath( $dir ) . '/tmp');

        defined('LD_LIB_DIR') or define('LD_LIB_DIR', realpath( $dir ) . '/lib' );

        defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

        defined('LD_RELEASE') OR define('LD_RELEASE', 'edge');

        if (constant('LD_DEBUG')) {
            if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                error_reporting( E_ALL ^ E_DEPRECATED );
            } else {
                error_reporting( E_ALL | E_NOTICE );
            }
        }

        set_include_path( LD_LIB_DIR . PATH_SEPARATOR . get_include_path() );

        self::$constantsDefined = true;
    }

    public static function registerAutoload()
    {
        // Zend Framework & Ld Libraries
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Ld_');

        // TODO: to be replaced by an Autoloader
        require_once 'clearbricks/common/lib.files.php';
        require_once 'clearbricks/zip/class.zip.php';
        require_once 'clearbricks/zip/class.unzip.php';

        self::$autoloadRegistered = true;
    }

    public static function loadSite($dir)
    {
        if (!self::$constantsDefined) {
            self::defineConstants($dir);
        }

        if (!self::$autoloadRegistered) {
            self::registerAutoload();
        }

        // Site configuration
        $config = self::$config = Ld_Files::getJson($dir . '/dist/config.json');
        if (empty($config['dir'])) {
            $config['dir'] = $dir;
        }
        if (empty($config['host']) && isset($_SERVER['SERVER_NAME'])) {
            $config['host'] = $_SERVER['SERVER_NAME'];
        }

        // Site object
        $site = self::$site = new Ld_Site_Local($config);
        Zend_Registry::set('site', $site);

        self::setupPlugins();
        self::setupAuthentication();
        self::setupLocales();

        // Legacy CSS Constant
        defined('H6E_CSS') OR define('H6E_CSS', $site->getUrl('css'));

        return $site;
    }

    public static function setupPlugins()
    {
        $active_plugins = isset(self::$config['active_plugins']) ? self::$config['active_plugins'] : array();

        foreach ($active_plugins as $plugin) {
            $plugin = strtolower($plugin);
            require_once self::$site->getDirectory('shared') . '/plugins/' . $plugin . '.php';
            $className = 'Ld_Plugin_' . Zend_Filter::filterStatic($plugin, 'Word_DashToCamelCase');
            if (class_exists($className, false) && method_exists($className, 'load')) {
                // $className::load(); // php 5.2 doesn't like this
                call_user_func(array($className, 'load'));
            }
        }
    }

    public static function setupAuthentication()
    {
        // Setup Authentication
        if (function_exists('mcrypt_ecb') && isset(self::$config['secret'])) {
            $cookieManager = new Ld_Cookie(self::$config['secret']);
        } else {
            $cookieManager = new Ld_Cookie_Simple();
        }
        $path = self::$site->getPath();
        $cookieConfig = array('cookieName' => 'ld-auth', 'cookiePath' => empty($path) ? '/' : $path);
        if (class_exists('Ld_Plugin')) {
            $cookieConfig = Ld_Plugin::apply('Loader:authCookieConfig', $cookieConfig);
        }
        $authStorage = new Ld_Auth_Storage_Cookie($cookieManager, $cookieConfig);
        $auth = Zend_Auth::getInstance();
        $auth->setStorage($authStorage);
    }

    public static function setupLocales()
    {
        $site = self::$site;

        // Locale
        $default_mo = $site->getDirectory('shared') . '/locales/ld/en_US/default.mo';
        if (file_exists($default_mo)) {
            $options = array();
            $options['disableNotices'] = true; // 'auto' locale can generate notices we better avoid
            $adapter = new Zend_Translate('gettext', $default_mo, 'en_US', $options);
            $locales = Ld_Files::getDirectories($site->getDirectory('shared') . '/locales/ld/', array('en_US'));
            foreach ($locales as $locale) {
                $adapter->addTranslation($site->getDirectory('shared') . "/locales/ld/$locale/default.mo", $locale);
            }
            if (isset($_COOKIE['ld-lang']) && $adapter->isAvailable($_COOKIE['ld-lang'])) {
                $adapter->setLocale($_COOKIE['ld-lang']);
            }
            Zend_Registry::set('Zend_Translate', $adapter);
        }
    }

}
