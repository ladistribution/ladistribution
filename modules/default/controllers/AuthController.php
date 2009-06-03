<?php

require_once 'Ld/Controller/Action.php';

/**
 * Auth controller
 */
class AuthController extends Ld_Controller_Action
{

    function indexAction()
    {
        if ($this->authenticated) {
            $this->_redirect( Zend_Registry::get('site')->getInstance('admin')->getUrl() );
        }
        $this->view->postParams = $_POST;
        $this->render('login');
    }

}
