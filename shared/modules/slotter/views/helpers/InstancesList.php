<?php

class View_Helper_InstancesList extends Zend_View_Helper_Abstract
{

    public function instancesList($all = true)
    {
        ?>

        <div class="ld-instance-list">
          <?php if ($this->view->userRole == 'admin') : ?>
              <ul class="blocks sortables mini">
          <?php else : ?>
              <ul class="blocks mini">
          <?php endif ?>

        <?php

        $instance = $this->view->instance;

        foreach (Zend_Registry::get('site')->getApplicationsInstances() as $id => $application) {

            if (!$all && $this->view->id != $id) {
                continue;
            }
            
            $className = 'sortable ';
            $className .= $application->getPackageId();

            $manageUrl = $this->view->url(array('controller' => 'instance', 'id' => $id), 'instance-action');

            printf('<li id="app_%s" class="%s">', $id, $className);

            printf('<div class="name">');
            printf('<a class="manage" href="%s">%s</a><br />', $application->getUrl(), $application->getName());
            printf('<span class="path">/%s/</span>', $application->getPath());
            printf('</div>');

            printf('<div class="links">');
            foreach ($application->getLinks() as $link) {
                  if ($link['type'] == 'text/html' && $link['title'] == 'admin') {
                      printf("<a class=\"view\" href=\"%s\">%s</a><br/>", $link['href'], ucfirst($link['title']));
                  }
            }
            if ($this->view->userRole == 'admin') {
                printf('<a class="view" href="%s">%s</a><br/>', $manageUrl, $this->translate("Settings"));
            }
            printf('</div>');

            printf("</li>\n");
        }

        ?>

        <?php if ($all && $this->view->userRole == 'admin') : ?>
        <li class="empty new">
            <a href="<?php echo $this->view->url(array('controller' => 'instance', 'action' => 'new')) ?>">
                <?php echo $this->translate("Add a new application") ?>
            </a>
        </li>
        <?php endif; ?>

          </ul>
        </div>

        <script type="text/javascript">
        (function($) {
            $(document).ready(function($){
                if (typeof Ld == "undefined") Ld = {};
                Ld.sortInstancesUrl = "<?php echo $this->view->url(
                    array('module' => 'slotter', 'controller' => 'index', 'action' => 'order', 'id' => null), 'default'); ?>";
            });
        })(jQuery);
        </script>

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
