<?php

class Api_IndexController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->noRender();
    }

    public function indexAction()
    {
        $this->getResponse()->appendBody('La Distribution API');
    }

}
