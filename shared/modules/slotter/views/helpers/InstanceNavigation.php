<?php

class View_Helper_InstanceNavigation extends Zend_View_Helper_Abstract
{

    public function instanceNavigation()
    {
        $site = $this->view->site;
        $instance = $this->view->instance;

        $installer = $instance->getInstaller();

        $preferences = $instance->getPreferences('configuration');
        $themes = $instance->getThemes();
        $themePreferences = $installer->getPreferences('theme');
        $extensions = $site->getPackageExtensions( $instance->getPackageId(), 'plugin' );
        $roles = $instance->getRoles();
        if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
            $colorSchemes = $instance->getColorSchemes();
        }
        if (Ld_Plugin::applyFilters('Instance:customCss', true)) {
            $customCss = method_exists($installer, 'setCustomCss');
        }

        echo '<ul class="ld-instance-menu h6e-tabs">' . "\n";

        $actions = array();
        $actions['configure'] = $this->translate('General');
        if (!empty($themes)) {
            $actions['themes'] = $this->translate('Themes');
        }
        if (!empty($colorSchemes)) {
            $actions['appearance'] = $this->translate('Colors');
        }
        if (!empty($customCss)) {
            $actions['css'] = $this->translate('CSS');
        }
        if (!empty($extensions)) {
            $actions['extensions'] = $this->translate('Extensions');
        }
        if (!empty($roles)) {
            $actions['roles'] = $this->translate('Roles');
        }
        $actions['backups'] = $this->translate('Backups');

        foreach ($actions as $action => $label) {
            $url = $this->view->instanceActionUrl($action, $this->view->id);
            $current = $this->view->action == $action;
            echo '<li' . ($current ? ' class="active"' : '') . '><a href="' . $url . '">' . $label . '</a></li>' . "\n";
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
