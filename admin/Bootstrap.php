<?php

class Bootstrap
{

    protected static $_front;

    protected static $_registry;

    public static function run($dir = null)
    {
        self::prepare($dir);

        self::dispatch();
    }

    public static function getSite()
    {
        return Zend_Registry::get('site');
    }

    public static function prepare($dir)
    {
        self::$_registry = Zend_Registry::getInstance();

        self::$_registry['instance'] = self::$_registry['site']->getInstance($dir);

        self::setupMvc();
        self::setupRoutes();
        self::setupCache();
        self::setupLocales();

        if ( get_magic_quotes_gpc() ) {
            $fn = array('self', '_stripslashesDeep');
            $_GET = array_map($fn, $_GET);
            $_POST = array_map($fn, $_POST);
            $_COOKIE = array_map($fn, $_COOKIE);
            $_REQUEST = array_map($fn, $_REQUEST);
        }
    }

    protected static function _stripslashesDeep($value)
    {
        $value = is_array($value) ? array_map(array('Bootstrap', '_stripslashesDeep'), $value) : stripslashes($value);
        return $value;
    }

    public static function setupMvc()
    {
        $site = self::$_registry['site'];
        $instance = self::$_registry['instance'];

        $mvc = Zend_Layout::startMvc(array('layoutPath' => $site->getDirectory('shared') . '/modules/default/views/layouts'));

        $mvc->getView()->addHelperPath('Ld/View/Helper', 'Ld_View_Helper');

        $mvc->getView()->css()->append('/h6e-minimal/h6e-minimal.css', 'h6e-minimal');
        $mvc->getView()->css()->append('/ld-ui/ld-ui.css', 'ld-ui');

        self::$_front = Zend_Controller_Front::getInstance();

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/slotter/controllers', 'slotter'
        );

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/merger/controllers', 'merger'
        );

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/identity/controllers', 'identity'
        );

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/default/controllers', 'default'
        );

        if (constant('LD_REWRITE') == false) {
            self::$_front->setBaseUrl( $site->getPath() . '/' . $instance->getPath() . '/index.php');
        }
    }

    public static function setupRoutes()
    {
        $config = new Zend_Config_Ini(dirname(__FILE__)  .'/routes.ini');
        $router = self::$_front->getRouter();
        $router->addConfig($config);
    }

    public static function setupLocales()
    {
        if (Zend_Registry::isRegistered('Zend_Translate')) {
            $site = self::getSite();
            $adapter = Zend_Registry::get('Zend_Translate');
            $locales = Ld_Files::getDirectories($site->getDirectory('shared') . '/locales/admin/');
            foreach ($locales as $locale) {
                $adapter->addTranslation($site->getDirectory('shared') . "/locales/admin/$locale/default.mo", $locale);
            }
        }
    }

    public static function setupCache()
    {
        $cacheDirectory = LD_TMP_DIR . '/cache/';

        Ld_Files::createDirIfNotExists($cacheDirectory);

        if (file_exists($cacheDirectory) && is_writable($cacheDirectory)) {
            $frontendOptions = array(
               'lifetime' => 60, // cache lifetime of 1 minute
               'automatic_serialization' => true
            );
            $backendOptions = array(
                'cache_dir' => $cacheDirectory
            );
            self::$_registry['cache'] = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        }
    }

    /**
     * Dispatch the request.
     */
    public static function dispatch()
    {
        self::$_front->dispatch();
    }

}
