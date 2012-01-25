<?php

require_once 'BaseController.php';

class Identity_IndexController extends Identity_BaseController
{

    public function profileAction()
    {
        $user = Ld_Auth::getUser();

        if (!defined('LD_SERVICES') || !constant('LD_SERVICES')) {
            if ($user && $user['id'] == $this->targetUser['id']) {
                $this->_setParam('action', 'edit');
                $this->_forward('edit', 'users', 'slotter');
            }
            if (Ld_Auth::isAdmin()) {
                $this->_setParam('action', 'edit');
                $this->_forward('edit', 'users', 'slotter');
            }
        }

        $this->view->services = $services = $this->_getServices();
    }

}
