<?php

require_once 'Ld/Controller/Action.php';

/**
 * Index controller
 */
class IndexController extends Ld_Controller_Action
{

    function indexAction()
    {
        $this->_setTitle('La Distribution Index');

        $directories = $this->getFrontController()->getDispatcher()->getControllerDirectory();
        $modules = array_keys($directories);

        foreach ($modules as $module) {
            if ($module != 'default') {
                 $this->_redirector->goto('index', 'index', $module);
                 return;
            }
        }

    }

}
