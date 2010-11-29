<?php

/**
 * Base controller
 */
class Slotter_BaseController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        if (empty($this->_registry['instance'])) {
            throw new Exception('No Instance defined.');
        }

        $this->view->addHelperPath(dirname(__FILE__) . '/../views/helpers/', 'View_Helper');

        $this->view->action = $this->action = $this->getRequest()->getActionName();

        $this->_setTitle( $this->site->getName() );

        $this->_initAcl();

        $this->view->userRole = $this->userRole = $this->admin->getUserRole();

        $this->_initNavigation();
    }

    protected function _initNavigation()
    {
        $t = $this->getTranslator();

        $settings = array();
        $settings[] = array( 'label' => $t->translate('General'), 'module' => 'slotter', 'controller' => 'settings' );
        if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
            $settings[] = array( 'label' => $t->translate('Colors'), 'module' => 'slotter', 'controller' => 'appearance' );
            if (Ld_Plugin::applyFilters('Site:customCss', true)) {
                $settings[] = array( 'label' => $t->translate('CSS'), 'module' => 'slotter', 'controller' => 'appearance', 'action' => 'css' );
            }
        }
        if (!$this->site->isChild()) {
            if (defined('LD_MULTI_DOMAINS') && constant('LD_MULTI_DOMAINS')) {
                $settings[] = array( 'label' => $t->translate('Domains'), 'module' => 'slotter', 'controller' => 'domains' );
            }
            $plugins = $this->site->getPlugins();
            if (!empty($plugins)) {
                $settings[] = array( 'label' => $t->translate('Extras'), 'module' => 'slotter', 'controller' => 'plugins' );
            }
        }

        $pages = array(
            array( 'label' => $t->translate('Home'), 'module' => 'slotter', 'route' => 'default',
                'pages' => array(
                    array( 'label' => $t->translate('Applications'), 'module'=> 'slotter', 'route' => 'default'),
                    array( 'label' => $t->translate('Ressources'), 'module' => 'slotter', 'controller' => 'repositories', 'pages' => array(
                        array( 'label' => $t->translate('Repositories'), 'module' => 'slotter', 'controller' => 'repositories' ),
                        array( 'label' => $t->translate('Databases'), 'module' => 'slotter', 'controller' => 'databases' ),
                    )),
                    array( 'label' => $t->translate('Users'), 'module' => 'slotter', 'controller' => 'users' ),
                    array( 'label' => $t->translate('Settings'), 'module' => 'slotter', 'controller' => 'settings', 'pages' => $settings),
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

        $resources = array('instances', 'repositories', 'databases', 'users', 'plugins', 'sites', 'domains', 'locales');
        foreach ($resources as $resource) {
            $this->_acl->add( new Zend_Acl_Resource($resource) );
            $this->_acl->allow('admin', $resource, 'manage');
        }

        $this->_acl->allow('admin', null, 'admin');
        $this->_acl->allow('user', 'instances', 'view');
        $this->_acl->allow('admin', 'instances', 'update');

        Ld_Plugin::doAction('Slotter:acl', $this->_acl);
    }

    protected function _disallow()
    {
        if ($this->authenticated) {
             $this->_forward('disallow', 'auth', 'default');
         } else {
             $this->_forward('login', 'auth', 'default');
         }
    }

}
