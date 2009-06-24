<?php

require_once 'Zend/Controller/Action.php';

/**
 * Ld Base Controller
 */
class Ld_Controller_Action extends Zend_Controller_Action
{

    function init()
    {
        // Registry
        $this->_registry = Zend_Registry::getInstance();

        // Controller Action Helpers
        $this->_helper->addPath(LD_LIB_DIR . '/Ld/Controller/Action/Helper/', 'Ld_Controller_Action_Helper_');

        // Redirector Helper
        $this->_redirector = $this->_helper->getHelper('Redirector');

        // Authentication
        $this->view->authenticated = $this->authenticated = $this->_helper->auth->authenticate();
        if ($this->authenticated) {
            // old, deprecated
            $this->view->user = $this->user = $this->_helper->auth->getUser();
            // new
            $this->view->currentUser = $this->currentUser = $this->_helper->auth->getUser();
        }
    }

    function _setTitle($title)
    {
        $this->view->title = $title;
        $this->view->headTitle($title, 'SET');
    }

}
