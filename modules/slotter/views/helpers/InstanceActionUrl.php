<?php

class View_Helper_InstanceActionUrl extends Zend_View_Helper_Abstract
{
    public function instanceActionUrl($action, $id = null)
    {
        $id = empty($id) ? $this->view->id : $id;
        $url = $this->view->url(array('controller' => 'instance', 'id' => $id, 'action' => $action));
        return $url;
    }
}
