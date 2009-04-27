<?php

require_once 'Ld/Controller/Action.php';

/**
 * Error controller
 */
class IndexController extends Ld_Controller_Action
{

    function indexAction()
    {
        $this->view->baseUrl = $this->getRequest()->getBaseUrl();

        $this->_setTitle('Index');
    }

}