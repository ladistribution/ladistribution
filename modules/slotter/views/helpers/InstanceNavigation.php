<?php

class View_Helper_InstanceNavigation extends Zend_View_Helper_Abstract
{
    public function instanceNavigation($currentAction = null)
    {
        $instance = $this->view->instance;
        
        // echo '<h1 class="h6e-page-title">' . $instance->getName() . '</h1>';
        
        echo "<div>\n";
        
        echo "<p>";
        if (defined('LD_MULTISITES') && TRUE == LD_MULTISITES) {
            echo '<a href="' . $this->view->url(array('site' => $this->view->site->id), 'site') . '">' . $this->view->site->name . '</a>';
            echo " > ";
        }
        echo "<strong>" . $instance->getName() . "</strong> ";
        echo ' (<a href="' . $instance->getUrl() . '">' . $instance->getUrl() . '</a>)';
        echo "</p>\n";
        
        echo "<p>";
        
        // echo "<strong>" . $instance->getName() . "</strong> ";
        // 
        // echo ' (<a href="' . $instance->getUrl() . '">' . $instance->getUrl() . '</a>) > ';
        
        $actions = array('manage', 'configure', 'themes', 'extensions', 'roles', 'backup');
        foreach ($actions as $action) {
            $url = $this->view->url(array('controller' => 'instance', 'id' => $this->view->id, 'action' => $action), 'instance-action');
            echo '<a href="' . $url . '">' . $action . '</a>';
            echo ' | ';
        }
        echo "</p>\n";
        
        echo "</div>\n";
    }
}