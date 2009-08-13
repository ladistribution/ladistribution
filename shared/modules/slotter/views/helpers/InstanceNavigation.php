<?php

class View_Helper_InstanceNavigation extends Zend_View_Helper_Abstract
{

    public function instanceNavigation()
    {
        $instance = $this->view->instance;

        echo '<ul class="ld-instance-menu">' . "\n";

        $actions = array(
            'status'     => $this->translate('status'),
            'configure'  => $this->translate('configure'),
            'themes'     => $this->translate('themes'),
            'extensions' => $this->translate('extensions'),
            'roles'      => $this->translate('roles'),
            'backups'    => $this->translate('backups')
        );

        foreach ($actions as $action => $label) {
            $url = $this->view->instanceActionUrl($this->view->id, $action);
            $current = $this->view->action == $action;
            echo '<li' . ($current ? ' class="current"' : '') . '><a href="' . $url . '">' . $label . '</a></li>' . "\n";
        }
        echo "</ul>\n";
    }

    protected function translate($string)
    {
        if (empty($this->view->translate)) {
            $this->view->translate = $this->view->getHelper('translate');
        }

        return $this->view->translate->translate($string);
    }

}
