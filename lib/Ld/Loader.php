<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Loader
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2012 h6e.net / François Hodierne (http://h6e.net/)
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

        defined('LD_TMP_DIR') or define('LD_TMP_DIR', realpath( $dir ) . '/tmp');

        defined('LD_LIB_DIR') or define('LD_LIB_DIR', realpath( $dir ) . '/lib' );

        defined('LD_SERVER') or define('LD_SERVER', 'http://ladistribution.net/');

        defined('LD_RELEASE') or define('LD_RELEASE', 'edge');

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
        $dir = realpath($dir);

        if (!self::$constantsDefined) {
            self::defineConstants($dir);
        }

        if (!self::$autoloadRegistered) {
            self::registerAutoload();
        }

        // Root Directory
        Zend_Registry::set('dir', $dir);

        // Site object
        $site = self::$site = new Ld_Site_Local(array('dir' => $dir));
        Zend_Registry::set('site', $site);

        self::setupPlugins();
        self::setupAuthentication();
        self::setupLocales();
        self::setupCache();

        // This constants should bet set after plugins are loaded
        defined('LD_DEBUG') or define('LD_DEBUG', false);
        defined('LD_REWRITE') or define('LD_REWRITE', true);
        defined('LD_BREADCRUMBS') or define('LD_BREADCRUMBS', false);
        defined('LD_APPEARANCE') or define('LD_APPEARANCE', true);
        defined('LD_NEWS_FEED') or define('LD_NEWS_FEED', false);
        defined('LD_MONGO_BACKEND') or define('LD_MONGO_BACKEND', false);
        defined('LD_SERVICES') or define('LD_SERVICES', false);

        defined('LD_SVN') or define('LD_SVN', Ld_Files::exists($dir . '/.svn') || Ld_Files::exists($dir . '/.git'));
        if (!constant('LD_SVN')) {
            defined('LD_COMPRESS_JS') or define('LD_COMPRESS_JS', true);
            defined('LD_COMPRESS_CSS') or define('LD_COMPRESS_CSS', true);
        }

        // Legacy CSS Constant
        defined('H6E_CSS') or define('H6E_CSS', $site->getUrl('css'));

        Ld_Plugin::doAction("Site:loaded", $site);

        return $site;
    }

    public static function loadSubSite($dir)
    {
        $dir = realpath($dir);

        // Site configuration
        self::$config = Ld_Files::getJson($dir . '/dist/config.json');
        if (empty(self::$config['dir'])) {
            self::$config['dir'] = $dir;
        }
        if (empty(self::$config['host']) && isset($_SERVER['HTTP_HOST'])) {
            self::$config['host'] = $_SERVER['HTTP_HOST'];
        }

        // Site object
        $parent = Zend_Registry::get('site');
        $site = self::$site = new Ld_Site_Child(self::$config, $parent);
        Zend_Registry::set('site', $site);

        self::loadPlugin('subsite');

        return $site;
    }

    public static function setupPlugins()
    {
        $active_plugins = self::$site->getConfig('active_plugins');
        if (empty($active_plugins)) {
            $active_plugins = array();
        }

        global $ld_global_plugins;
        if (isset($ld_global_plugins)) {
            $active_plugins = array_merge($ld_global_plugins, $active_plugins);
            $active_plugins = array_unique($active_plugins);
        }

        foreach ($active_plugins as $plugin) {
            self::loadPlugin($plugin);
        }
    }

    public static function loadPlugin($plugin)
    {
        $className = 'Ld_Plugin_' . Zend_Filter::filterStatic($plugin, 'Word_DashToCamelCase');
        if (class_exists($className, false) == false) {
            $filename = self::$site->getDirectory('shared') . '/plugins/' . $plugin . '.php';
            $alternativeFilename = self::$site->getDirectory('shared') . '/plugins/' . $plugin . '/' . $plugin . '.php';
            if (Ld_Files::exists($filename)) {
                require_once $filename;
            } elseif (Ld_Files::exists($alternativeFilename)) {
                require_once $alternativeFilename;
            }
        }
        // Autoload is useful for Plugins located in Ld/Plugin/{Name}.php
        if (class_exists($className) && method_exists($className, 'load')) {
            $class = new $className;
            $class->load();
        }
    }

    public static function setupAuthentication()
    {
        // Setup Authentication
        $secret = self::$site->getConfig('secret');
        if (empty($secret)) {
            $secret = Ld_Auth::generatePhrase();
            self::$site->setConfig('secret', $secret);
        }
        if (function_exists('mcrypt_ecb')) {
            $cookieManager = new Ld_Cookie($secret);
        } else {
            $cookieManager = new Ld_Cookie_Simple();
        }
        $path = self::$site->getPath();
        $cookieConfig = array('cookieName' => 'ld-auth', 'cookiePath' => empty($path) ? '/' : $path);
        if (class_exists('Ld_Plugin')) {
            $cookieConfig = Ld_Plugin::applyFilters('Loader:authCookieConfig', $cookieConfig);
        }
        $authStorage = new Ld_Auth_Storage_Cookie($cookieManager, $cookieConfig);
        $auth = Zend_Auth::getInstance();
        $auth->setStorage($authStorage);
    }

    public static function setupLocales()
    {
        $timezone = self::$site->getConfig('timezone');
        if (!empty($timezone)) {
            date_default_timezone_set($timezone);
        }

        $dir = self::$site->getDirectory('shared') . '/locales';

        $default_mo = $dir . '/ld/en_US/default.mo';
        if (Ld_Files::exists($default_mo)) {
            $options = array();
            $options['disableNotices'] = true; // 'auto' locale can generate notices we better avoid
            $adapter = new Zend_Translate('gettext', $default_mo, 'en_US', $options);
            $locales = Ld_Files::getDirectories($dir . '/ld/', array('en_US'));
            foreach ($locales as $locale) {
                $adapter->addTranslation($dir . "/ld/$locale/default.mo", $locale);
                if (Ld_Files::exists($dir . "/admin/$locale/default.mo")) {
                    $adapter->addTranslation($dir . "/admin/$locale/default.mo", $locale);
                }
            }
            if (isset($_COOKIE['ld-lang']) && $adapter->isAvailable($_COOKIE['ld-lang'])) {
                $adapter->setLocale($_COOKIE['ld-lang']);
            }
            Zend_Registry::set('Zend_Translate', $adapter);
        }
    }

    public static function setupCache()
    {
        $cacheDirectory = LD_TMP_DIR . '/cache/';

        Ld_Files::createDirIfNotExists($cacheDirectory);

        if (Ld_Files::exists($cacheDirectory) && is_writable($cacheDirectory)) {
            $frontendOptions = array(
                'lifetime' => self::$site->getConfig('cache_lifetime', 300), // cache lifetime of 5 minutes
                'automatic_serialization' => true
            );
            $backendOptions = array(
                'cache_dir' => $cacheDirectory
            );
            $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
            Zend_Registry::set('cache', $cache);
        }
    }

}
