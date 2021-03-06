<?php

class View_Helper_InstanceWidget extends Zend_View_Helper_Abstract
{

    public function instanceWidget()
    {
        $application = $this->view->instance;

        ?>

        <ul class="ld-nav">
            <li><a href="<?php echo $application->getUrl() ?>"><?php echo $this->translate("Back to the application") ?></a></li>
        </ul>

        <h2><?php printf($this->translate("Settings <small>for %s</small>"), '<strong>' . $application->getName() . '</strong>') ?></h2>

        <?php return ?>

        <ul class="blocks mini instance-widget">

        <?php

        $id = $application->getId();
        $className = $application->getPackageId();

        $manageUrl = $this->view->url(array('controller' => 'instance', 'id' => $id), 'instance-action', true);

        printf('<li id="app_%s" class="%s">', $id, $className);

        printf('<div class="name">');
        printf('<a class="manage" href="%s">%s</a><br />', $application->getUrl(), $application->getName());
        printf('<span class="path">/%s/</span>', $application->getPath());
        printf('</div>');

        printf('<div class="infos">');
        printf($this->translate("Package: %s"), $application->getPackageId());
        printf('<br/>');
        printf($this->translate("Version: %s"), $application->getVersion());
        printf('</div>');

        printf("</li>\n");

        ?>

        </ul>

        <?php
    }

    protected function translate($string)
    {
        if (empty($this->view->translate)) {
            $this->view->translate = $this->view->getHelper('translate');
        }

        return $this->view->translate->translate($string);
    }

}
