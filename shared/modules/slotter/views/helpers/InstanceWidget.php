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

        <ul class="blocks mini">

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
        printf('Package: %s<br/>', $application->getPackageId());
        printf('Version: %s', $application->getVersion());
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
