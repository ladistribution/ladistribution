<?php

class View_Helper_InstanceNavigation extends Zend_View_Helper_Abstract
{

    public function instanceNavigation()
    {
        $instance = $this->view->instance;

        echo '<ul class="ld-instance-menu">' . "\n";

        $actions = array('status', 'configure', 'themes', 'extensions', 'roles', 'backups');
        foreach ($actions as $action) {
            $url = $this->view->url(array('controller' => 'instance', 'id' => $this->view->id, 'action' => $action), 'instance-action');
            $current = $this->view->action == $action;
            echo '<li' . ($current ? ' class="current"' : '') . '><a href="' . $url . '">' . $action . '</a></li>' . "\n";
        }
        echo "</ul>\n";
    }

}
