<?php

require_once 'BaseController.php';

class Identity_IndexController extends Ld_Controller_Action
{

    public function profileAction()
    {
        if ($this->_hasParam('id')) {
            $this->view->user = $this->targetUser = $this->site->getUser( $this->_getParam('id') );
        }

        if (empty($this->targetUser)) {
            throw new Exception('Unknown user.');
        }

        $user = Ld_Auth::getUser();
        if ($user && $user['id'] == $this->targetUser['id']) {
            $this->_setParam('action', 'edit');
            $this->_forward('edit', 'users', 'slotter');
        }

        if (Ld_Auth::isAdmin()) {
            $this->_setParam('action', 'edit');
            $this->_forward('edit', 'users', 'slotter');
        }
    }

}
