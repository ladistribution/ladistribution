<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

/**
 * @see Zend_Controller_Action
 */
require_once 'Zend/Controller/Action.php';

class Ld_Controller_Action extends Zend_Controller_Action
{

    function init()
    {
        // Registry
        $this->_registry = Zend_Registry::getInstance();

        // Controller Action Helpers
        $this->_helper->addPath('Ld/Controller/Action/Helper/', 'Ld_Controller_Action_Helper_');

        // Redirector Helper
        $this->_redirector = $this->_helper->getHelper('Redirector');

        // Authentication
        $this->view->authentication = $this->authentication = $this->_helper->auth->authenticate();
        $this->view->authenticated = $this->authenticated = $this->_helper->auth->isAuthenticated();
        if ($this->authenticated) {
            // old, deprecated
            $this->view->user = $this->user = $this->_helper->auth->getUser();
            // new
            $this->view->currentUser = $this->currentUser = $this->_helper->auth->getUser();
        }
    }

    function getTranslator()
    {
        $translator = $this->view->getHelper('translate');
        return $translator;
    }

    function getSite()
    {
        return Zend_Registry::get('site');
    }

    function setTitle($title)
    {
        $this->view->title = $title;
        $this->view->headTitle($title, 'SET');
    }

    function appendTitle($title)
    {
        $this->view->headTitle( ' | ' . $title );
    }

    function noRender()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Layout::getMvcInstance()->disableLayout();
    }
    
    // Legacy
    
    function _setTitle($title) { return $this->setTitle($title); }
}
