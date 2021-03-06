<?php

require_once 'Ld/Controller/Action.php';

/**
 * Index controller
 */
class IndexController extends Ld_Controller_Action
{

    function indexAction()
    {
        $this->_setTitle('Index');

        $directories = $this->getFrontController()->getDispatcher()->getControllerDirectory();
        $modules = array_keys($directories);

        foreach ($modules as $module) {
            if ($module != 'default') {
                 // $this->_redirector->goto('index', 'index', $module);
                 $router = Zend_Controller_Front::getInstance()->getRouter();
                 $router->setGlobalParam('module', $module);
                 $this->_forward('index', 'index', $module);
                 return;
            }
        }

    }

}
