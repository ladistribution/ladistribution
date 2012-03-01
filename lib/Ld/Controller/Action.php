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

        $this->view->authenticated = $this->authenticated = Ld_Auth::isAuthenticated();
        if ($this->authenticated) {
            // old, deprecated
            $this->view->user = $this->user = Ld_Auth::getUser();
            // new
            $this->view->currentUser = $this->currentUser = Ld_Auth::getUser();
        }

        // User Role
        $this->view->userRole = $this->userRole = $this->admin->getUserRole();

        // ACL
        $this->_acl = $this->admin->getAcl();

        // Locale
        $this->initLocale();

        // Title
        if ($site = $this->getSite()) {
            if ($this->site->isChild()) {
                $this->setTitle( $site->getParentSite()->getName() . ' | ' . $site->getName() );
            } else {
                $this->setTitle( $site->getName() );
            }
        }

        // Flash Messenger
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    }

    function initLocale()
    {
        if ($this->_hasParam('ld-lang')) {
            $path = $this->getSite()->getPath();
            $cookiePath = empty($path) ? '/' : $path;
            $locale = $_COOKIE['ld-lang'] = $this->_getParam('ld-lang');
            setcookie('ld-lang', $locale, time() + 365 * 24 * 60 * 60, $cookiePath);
        } elseif ($this->getRequest()->getCookie('ld-lang')) {
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

    function translate($string)
    {
        if (empty($this->_translator)) {
            $this->_translator = $this->getTranslator();
        }
        return $this->_translator->translate($string);
    }

    function getSite()
    {
        if (isset($this->site)) {
            return $this->site;
        }
        if (Zend_Registry::isRegistered('site')) {
            return $this->site = Zend_Registry::get('site');
        }
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
        return $this->admin->userCan($action, $ressource);
    }

    function disableLayout()
    {
        Zend_Layout::getMvcInstance()->disableLayout();
    }

    function noRender()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->disableLayout();
    }

    function disallow()
    {
        if ($this->authenticated) {
            $this->_forward('disallow', 'auth', 'default');
        } else {
            $this->_forward('login', 'auth', 'default');
        }
    }

    function redirectTo($url)
    {
        if (defined('LD_DEBUG') && constant('LD_DEBUG')) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->getResponse()
                ->appendBody( sprintf('<h2>%s</h2>', $this->translate('Ok') ) )
                ->appendBody( sprintf('<p><a href="%s">%s</a></p>', $url, $this->translate('Continue') ) );
        } else {
            $this->noRender();
            $this->_redirector->gotoUrl($url, array('prependBase' => false));
        }
    }

    // Legacy

    function _setTitle($title) { return $this->setTitle($title); }
}
