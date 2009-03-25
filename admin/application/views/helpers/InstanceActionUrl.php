<?php

class View_Helper_InstanceActionUrl extends Zend_View_Helper_Abstract
{
    public function instanceActionUrl($action, $id = null)
    {
        $params = array(
            'id' => isset($id) ? $id : $this->view->instance->path,
            'site' => $this->view->site->id,
            'action' => $action
        );
        $url = $this->view->url($params, 'instance-action');
        $url = str_replace("%2F", "/", $url);
        return $url;
    }
}
