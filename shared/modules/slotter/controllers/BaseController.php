<?php

require_once 'Zend/Controller/Action.php';

/**
 * Base controller
 */
class Slotter_BaseController extends Ld_Controller_Action
{

    protected static $_accept = array(
        'application/json' => 'json'
    );

    public function init()
    {
        parent::init();

        $this->view->site = $this->site = $this->_registry['site'];

        if (empty($this->_registry['instance'])) {
            throw new Exception('No Instance defined.');
        }

        if ($this->_hasParam('ld-lang')) {
            setCookie('ld-lang', $this->_getParam('ld-lang'), mktime() + 365 * 24 * 60 * 60, $this->site->getPath());
        }

        $this->view->setHelperPath(dirname(__FILE__) . '/../views/helpers/', 'View_Helper');

        $this->view->action = $this->getRequest()->getActionName();

        $this->_setTitle('Admin');

        $this->_initAcl();

        $this->_initLang();

        $this->_initNavigation();
    }

    protected function _initNavigation()
    {
        $translator = $this->getTranslator();

        $pages = array(
            array( 'label' => $translator->translate('Home'), 'module' => 'default', 'route' => 'default',
                'pages' => array(
                    array( 'label' => $translator->translate('Applications'), 'module'=> 'slotter', 'route' => 'default'),
                    array( 'label' => $translator->translate('Repositories'), 'module' => 'slotter', 'controller' => 'repositories' ),
                    array( 'label' => $translator->translate('Databases'), 'module' => 'slotter', 'controller' => 'databases' ),
                    array( 'label' => $translator->translate('Users'), 'module' => 'slotter', 'controller' => 'users' )
            ))
        );

        $this->_container = new Zend_Navigation($pages);
        $this->view->navigation($this->_container);
    }

    protected function _initAcl()
    {
        $this->_acl = new Zend_Acl();

        $guest = new Zend_Acl_Role('guest');
        $this->_acl->addRole($guest);
        $user = new Zend_Acl_Role('user');
        $this->_acl->addRole($user, $guest);
        $admin = new Zend_Acl_Role('admin');
        $this->_acl->addRole($admin, $user);

        $instances = new Zend_Acl_Resource('instances');
        $this->_acl->add($instances);

        $this->_acl->allow('admin', null, 'admin');
        $this->_acl->allow('user', 'instances', 'view');
        $this->_acl->allow('admin', 'instances', 'admin');

        $this->view->userRole = $this->userRole = $this->_getCurrentUserRole();
    }

    protected function _getCurrentUserRole()
    {
        $users = $this->site->getUsers();
        if (empty($users)) {
            return 'admin';
        } else if (isset($this->user)) {
            $username = $this->user['username'];
            $roles = $this->_registry['instance']->getUserRoles();
            if (isset($roles[$username])) {
                return $roles[$username];
            }
            return 'user';
        }
        return 'guest';
    }

    protected function _initLang()
    {
        if ($this->_hasParam('ld-lang')) {
            $locale = $this->_getParam('ld-lang');
        } else if ($this->getRequest()->getCookie('ld-lang')) {
            $locale = $this->getRequest()->getCookie('ld-lang');
        }
        if (isset($locale) &&  Zend_Registry::isRegistered('Zend_Translate')) {
            $translate = Zend_Registry::get('Zend_Translate');
            $translate->setLocale($locale);
        }
    }

    protected function _disallow()
    {
        if ($this->authenticated) {
             $this->_forward('disallow', 'auth', 'default');
         } else {
             $this->_forward('login', 'auth', 'default');
         }
    }

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        if ($this->getRequest()->isPost()) {
            $this->_contentType = $_SERVER['CONTENT_TYPE'];
            // TEMP: handle a particular context when interacting with requests in oAuth library
            if ($this->_contentType == 'application/octet-stream') {
                $this->_contentType == 'application/json';
            }
            if ($this->_contentType == 'application/json') {
                $params = Zend_Json::decode( $this->getRequest()->getRawBody() );
                $this->getRequest()->setParams($params);
                // TEMP: useful too with oAuth library
                if (!$this->_hasParam('format')) {
                    $this->_setParam('format', 'json');
                }
            }
        }

        $this->_handleFormat();
    }
    
    public function postDispatch()
    {
        switch ($this->_format) {
            case 'json':
                $this->_helper->viewRenderer->setNoRender(true);
                $this->view->jsonRenderer($this->view->getVars(), true);
                break;
            default:
                break;
        }
    }
    
    protected function _handleFormat()
    {
        $this->_format = $this->getRequest()->getParam('format');
        if (empty($this->_format)) {
            $this->_format = 'xhtml';
        }
        
        if (isset($_SERVER['HTTP_ACCEPT'])) {
          $accept = $_SERVER['HTTP_ACCEPT'];
          if (isset(self::$_accept[$accept])) {
              $this->_format = self::$_accept[$accept];
          }
        }
        
        if ($this->_format != 'xhtml') {
          $viewRenderer = Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer');
          $viewRenderer->setViewSuffix($this->_format . ".php");
          $this->_helper->layout->disableLayout();
        }
    }

}