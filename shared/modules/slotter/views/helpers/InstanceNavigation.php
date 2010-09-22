<?php

class View_Helper_InstanceNavigation extends Zend_View_Helper_Abstract
{

    public function instanceNavigation()
    {
        $site = $this->view->site;
        $instance = $this->view->instance;

        $preferences = $instance->getPreferences('configuration');
        $themes = $instance->getThemes();
        $extensions = $site->getPackageExtensions( $instance->getPackageId() );
        $roles = $instance->getRoles();

        echo '<ul class="ld-instance-menu">' . "\n";

        $actions = array();
        $actions['status'] = $this->translate('status');
        if (!empty($preferences)) {
            $actions['configure'] = $this->translate('configure');
        }
        if (!empty($themes)) {
            $actions['themes'] = $this->translate('themes');
        }
        if (!empty($extensions)) {
            $actions['extensions'] = $this->translate('extensions');
        }
        if (!empty($roles)) {
            $actions['roles'] = $this->translate('roles');
        }
        $actions['backups']   =  $this->translate('backups');

        foreach ($actions as $action => $label) {
            $url = $this->view->instanceActionUrl($action, $this->view->id);
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
