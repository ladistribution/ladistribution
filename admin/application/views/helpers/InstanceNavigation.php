<?php

class View_Helper_InstanceNavigation extends Zend_View_Helper_Abstract
{
    public function instanceNavigation($currentAction = null)
    {
        $instance = $this->view->instance;
        
        echo "<div>\n";
        
        echo "<p>";
        if (defined('LD_MULTISITES') && TRUE == LD_MULTISITES) {
            echo '<a href="' . $this->view->url(array('site' => $this->view->site->id), 'site') . '">' . $this->view->site->name . '</a>';
            echo " > ";
        }
        echo $instance->getName();
        echo ' (<a target="_blank" href="' . $instance->getUrl() . '">' . $instance->getUrl() . '</a>)';
        echo "</p>\n";
        echo "<p>";
        
        $actions = array('manage', 'configure', 'themes', 'extensions');
        foreach ($actions as $action) {
            echo '<a href="' . $this->view->instanceActionUrl($action) . '">' . $action . '</a>';
            echo ' | ';
        }
        echo "</p>\n";
        
        echo "</div>\n";
    }
}