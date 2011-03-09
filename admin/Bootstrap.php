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

        self::$_front = Zend_Controller_Front::getInstance();

        self::setupRoutes();
        self::setupMvc();
        // self::setupCache();
        // self::setupLocales();

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
        
        $configuration = $instance->getConfiguration();

        $mvc = Zend_Layout::startMvc(array('layoutPath' => $site->getDirectory('shared') . '/modules/default/views/layouts'));

        $view = $mvc->getView();

        $view->addHelperPath('Ld/View/Helper', 'Ld_View_Helper');

        if (defined('LD_COMPRESS_CSS') && constant('LD_COMPRESS_CSS')) {
            $view->css()->append('/h6e-minimal/h6e-minimal.compressed.css', 'h6e-minimal');
            $view->css()->append('/ld-ui/ld-ui.compressed.css', 'ld-ui');
        } else {
            $view->css()->append('/h6e-minimal/h6e-minimal.css', 'h6e-minimal');
            $view->css()->append('/ld-ui/ld-ui.css', 'ld-ui');
        }

        if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
            $view->headLink()->appendStylesheet(Ld_Ui::getSiteStyleUrl(), 'screen');
        }

        $view->js()->append('/jquery/jquery.js', 'js-jquery');
        $view->js()->append('/jquery/jquery-ui.js', 'js-jquery-ui');

        if (defined('LD_COMPRESS_JS') && constant('LD_COMPRESS_JS')) {
            $view->js()->append('/ld/ld.c.js', 'lib-admin');
        } else {
            $view->js()->append('/ld/ld.js', 'lib-admin');
        }

        $modules = array('slotter', 'merger', 'identity', 'default');

        if (isset($configuration['default_module'])) {
            if (in_array($configuration['default_module'], $modules)) {
                array_unshift($modules, $configuration['default_module']);
                $modules = array_unique($modules);
            }
        }

        foreach ($modules as $module) {
            self::$_front->addControllerDirectory(
                $site->getDirectory('shared') . "/modules/$module/controllers", $module
            );
        }

        // Routes Admin modules in various contexts

        if (defined('LD_ROOT_CONTEXT')) {
            $baseUrl = $site->getPath();
        } else {
            $baseUrl = $site->getPath() . '/' . $instance->getPath();
        }

        $indexScript = $site->getDirectory() . '/index.php';

        if ($site->getConfig('root_admin') == 1 && constant('LD_REWRITE') == true && file_exists($indexScript)) {
            $baseUrl = $site->getPath();
            $path = str_replace($baseUrl, '', $_SERVER["REQUEST_URI"]);
            if ($path == '/' . $instance->getPath() . '/') {
                $redirect = $baseUrl . '/' . $modules[0];
            } else if (strpos($path, $instance->getPath()) === 1) {
                $redirect = str_replace('/' . $instance->getPath() , '', $_SERVER["REQUEST_URI"]);
            }
        }

        if (constant('LD_REWRITE') == false) {
            $baseUrl = $site->getPath() . '/' . $instance->getPath() . '/index.php';
        }

        if (isset($redirect)) {
            header('Location:' . $redirect);
            exit;
        }

        if (isset($baseUrl)) {
            self::$_front->setBaseUrl($baseUrl);
        }

    }

    public static function setupRoutes()
    {
        $config = new Zend_Config_Ini(dirname(__FILE__)  .'/routes.ini');
        $router = self::$_front->getRouter();
        $router->addConfig($config);
    }

    // public static function setupLocales()
    // {
    //     if (Zend_Registry::isRegistered('Zend_Translate')) {
    //         $site = self::getSite();
    //         $adapter = Zend_Registry::get('Zend_Translate');
    //         $locales = Ld_Files::getDirectories($site->getDirectory('shared') . '/locales/admin/');
    //         foreach ($locales as $locale) {
    //             $adapter->addTranslation($site->getDirectory('shared') . "/locales/admin/$locale/default.mo", $locale);
    //         }
    //     }
    // }

    // public static function setupCache()
    // {
    //     $cacheDirectory = LD_TMP_DIR . '/cache/';
    // 
    //     Ld_Files::createDirIfNotExists($cacheDirectory);
    // 
    //     if (file_exists($cacheDirectory) && is_writable($cacheDirectory)) {
    //         $frontendOptions = array(
    //            'lifetime' => 60, // cache lifetime of 1 minute
    //            'automatic_serialization' => true
    //         );
    //         $backendOptions = array(
    //             'cache_dir' => $cacheDirectory
    //         );
    //         self::$_registry['cache'] = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    //     }
    // }

    /**
     * Dispatch the request.
     */
    public static function dispatch()
    {
        self::$_front->dispatch();
    }

}
