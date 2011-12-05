<?php

class Identity_BaseController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->appendTitle( $this->translate('Identity') );

        $this->view->addHelperPath(dirname(__FILE__) . '/../../slotter/views/helpers/', 'View_Helper');
    }

    public function preDispatch()
    {
        parent::preDispatch();

        if ($this->_hasParam('id')) {
            $this->view->targetUser = $this->targetUser = $this->site->getUser( $this->_getParam('id') );
            if (empty($this->targetUser)) {
                throw new Exception('Unknown user.');
            }
        } else if ($user = Ld_Auth::getUser()) {
            $this->view->targetUser = $this->targetUser = $user;
        } else {
            return $this->disallow();
        }

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        if (isset($this->user)) {

            $id = isset($this->user['username']) ? $this->user['username'] : $this->user['id'];

            $pages = array(
                array( 'label' => $this->translate('Identity'),
                       'route' => 'vanity', 'params' => array('id' => $id),
                    'pages' => array(
                        array( 'label' => $this->translate('General'),
                               'route' => 'vanity', 'params' => array( 'id' => $id )),
                        array( 'label' => $this->translate('Identities'),
                               'module'=> 'identity', 'controller' => 'accounts', 'route' => 'identity-accounts'),
                        array( 'label' => $this->translate('Authorizations'),
                               'module' => 'identity', 'controller' => 'authorizations', 'route' => 'identity-authorizations')
                ))
            );

            $container = new Zend_Navigation($pages);
            $this->view->navigation($container);

        }
    }

}
