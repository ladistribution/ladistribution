<?php

require_once 'Ld/Controller/Action.php';

/**
 * Error controller
 */
class AuthController extends Ld_Controller_Action
{

    function indexAction()
    {
        $this->view->postParams = $_POST;
        $this->render('login');
    }

}