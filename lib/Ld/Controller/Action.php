<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
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

        $this->view->site = $this->site = $this->_registry['site'];

        $this->view->admin = $this->admin = $this->_registry['instance'];

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

        // Use Role
        $this->view->userRole = $this->userRole = $this->admin->getUserRole();

        // ACL
        $this->_acl = $this->admin->getAcl();

        // Locale
        $this->initLocale();
        
        // Title
        if ($site = $this->getSite()) {
            $this->setTitle( $site->getName() );
        }
    }

    function initLocale()
    {
        if ($this->_hasParam('ld-lang')) {
            $path = $this->getSite()->getPath();
            $cookiePath = empty($path) ? '/' : $path;
            $locale = $_COOKIE['ld-lang'] = $this->_getParam('ld-lang');
            setCookie('ld-lang', $locale, time() + 365 * 24 * 60 * 60, $cookiePath);
        } else if ($this->getRequest()->getCookie('ld-lang')) {
            $locale = $this->getRequest()->getCookie('ld-lang');
        }
        if (isset($locale) &&  Zend_Registry::isRegistered('Zend_Translate')) {
            $translate = Zend_Registry::get('Zend_Translate');
            $translate->setLocale($locale);
        }
    }

    function getTranslator()
    {
        $translator = $this->view->getHelper('translate');
        return $translator;
    }

    function getSite()
    {
        if (Zend_Registry::isRegistered('site')) {
            return Zend_Registry::get('site');
        }
        return null;
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

    function userCan($action, $ressource = null)
    {
        return $this->_acl->isAllowed($this->userRole, 'databases', 'manage');
    }

    function noRender()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        Zend_Layout::getMvcInstance()->disableLayout();
    }
    // Legacy

    function _setTitle($title) { return $this->setTitle($title); }
}
