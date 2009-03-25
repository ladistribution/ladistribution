<?php

/**
 * Bootstrapping.
 */
class Bootstrap
{

    /**
     * Front controller.
     *
     * @var Zend_Controller_Front
     */
    public static $frontController;

    /**
     * Router.
     *
     * @var Zend_Controller_Router
     */
    public static $router;

    /**
     * Registry.
     *
     * @var Zend_Registry
     */
    public static $registry;

    public static $configurationFile = null;

    /**
     * Main bootstrapping function.
     */
    public static function run($config = array())
    {
        self::prepare();

        if (file_exists(self::$configurationFile)) {
            $config = Zend_Json::decode(file_get_contents(self::$configurationFile));
        }

        if (empty($config['sites'])) {
            $config['sites'] = array();
        }

        $config['sites']['default'] = array('type' => 'local', 'path' => '', 'name' => 'Local', 'slots' => 5);

        self::$registry['config'] = $config;

        self::$registry['sites'] = $config['sites'];

        self::dispatch();
    }

    /**
     * Retrieves the front controller and registry instances.
     */
    public static function prepare()
    {
        // Clearbricks
        require_once 'clearbricks/common/lib.files.php';
        require_once 'clearbricks/zip/class.zip.php';
        require_once 'clearbricks/zip/class.unzip.php';

        // Zend Framework
        require_once 'Zend/Loader.php';
        Zend_Loader::registerAutoload();

        self::$registry = Zend_Registry::getInstance();

        self::setupMvc();
        self::setupRoutes();

        if ( get_magic_quotes_gpc() ) {
            $_GET = array_map(array('Bootstrap', '_stripslashesDeep'), $_GET);
            $_POST = array_map(array('Bootstrap', '_stripslashesDeep'), $_POST);
            $_COOKIE = array_map(array('Bootstrap', '_stripslashesDeep'), $_COOKIE);
            $_REQUEST = array_map(array('Bootstrap', '_stripslashesDeep'), $_REQUEST);
        }

        self::$configurationFile = APPLICATION . '/../dist/configuration.json';
    }

    protected static function _stripslashesDeep($value)
    {
        $value = is_array($value) ? array_map(array('Bootstrap', '_stripslashesDeep'), $value) : stripslashes($value);
        return $value;
    }

    public static function setupMvc()
    {
        defined('APPLICATION') OR define('APPLICATION', dirname(__FILE__));

        self::$frontController = Zend_Controller_Front::getInstance();
        self::$frontController->setControllerDirectory(APPLICATION . '/controllers');

        if (false === LD_REWRITE) {
            self::$frontController->setBaseUrl(LD_BASE_PATH . '/admin/index.php');
        } else {
            self::$frontController->setBaseUrl(LD_BASE_PATH . '/admin/');
        }

        Zend_Layout::startMvc(array('layoutPath' => APPLICATION . '/views/layouts'));
    }

    public static function setupRoutes()
    {
        self::$router = self::$frontController->getRouter();

        if (defined('LD_MULTISITES') && true === LD_MULTISITES) {
            $base_route = 'sites/:site/';
            $base_route_regexp = 'sites/([^/]+)/';
        } else {
            $base_route = '';
            $base_route_regexp = '';
            self::$frontController->setDefaultControllerName('sites');
        }

        if (defined('LD_MULTISITES') && true === LD_MULTISITES) {

            $route = new Zend_Controller_Router_Route_Regex(
                $base_route_regexp . '(.*)/(manage|extensions|configure|themes|update|delete|backup|restore)',
                array(
                    'controller' => 'instance',
                    'action'     => 'manage'
                ),
                array(
                    1 => 'site',
                    2 => 'id',
                    3 => 'action'
                ),
                'sites/%s/%s/%s'
            );
            self::$router->addRoute('instance-action', $route);

        } else {

          $route = new Zend_Controller_Router_Route_Regex(
              $base_route_regexp . '(.*)/(manage|extensions|configure|themes|update|delete|backup|restore)',
              array('controller' => 'instance', 'action' => 'manage'),
              array(1 => 'id', 2 => 'action'),
              $base_route . '%s/%s'
          );
          self::$router->addRoute('instance-action', $route);

        }

        // It Would ne nice to move all this routes to default ones

        $route = new Zend_Controller_Router_Route(
            $base_route . 'instances/:action',
            array('action' => 'index', 'controller' => 'instance')
        );
        self::$router->addRoute('instances-action', $route);

        $route = new Zend_Controller_Router_Route(
            $base_route . 'databases/:action',
            array('action' => 'index', 'controller' => 'databases')
        );
        self::$router->addRoute('databases-action', $route);
        
        $route = new Zend_Controller_Router_Route(
            $base_route . 'users/:action',
            array('action' => 'index', 'controller' => 'users')
        );
        self::$router->addRoute('users-action', $route);

        $route = new Zend_Controller_Router_Route(
            'sites/:site',
            array('controller' => 'sites')
        );
        self::$router->addRoute('site', $route);

        $route = new Zend_Controller_Router_Route(
            'sites/new',
            array('controller' => 'sites', 'action' => 'new')
        );
        self::$router->addRoute('site-new', $route);

        $route = new Zend_Controller_Router_Route(
            'packages/:id/:action',
            array('controller' => 'packages')
        );
        self::$router->addRoute('package', $route);
    }

    /**
     * Dispatch the request.
     */
    public static function dispatch()
    {
        self::$frontController->dispatch();
    }

}
