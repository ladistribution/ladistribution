<?php

class View_Helper_PartialNavigationMenu extends Zend_View_Helper_Abstract
{

    public function partialNavigationMenu($label)
    {
        $translator = $this->view->getHelper('translate');
        $navigation = $this->view->navigation();
        $container  = $navigation->getContainer();
        $ressources = $container->findOneByLabel( $translator->translate($label) );
        echo $navigation->menu($ressources)->renderMenu(null, array('ulClass' => 'ld-instance-menu h6e-tabs', 'maxDepth' => 0));
    }

}
