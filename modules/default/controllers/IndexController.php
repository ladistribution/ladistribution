<?php

require_once 'Ld/Controller/Action.php';

/**
 * Index controller
 */
class IndexController extends Ld_Controller_Action
{

    function indexAction()
    {
        $this->view->baseUrl = $this->getRequest()->getBaseUrl();

        $this->_redirector->goto('index', 'index', 'slotter');
    }

}
