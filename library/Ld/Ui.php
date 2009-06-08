<?php

class Ld_Ui
{

    function getInstanceByPackageId($packageId)
    {
        $site = Zend_Registry::get('site');
        $applications = $site->getInstances('application');
        foreach ($applications as $id => $infos) {
            $application = $site->getInstance($id);
            if ($application->getPackageId() == $packageId) {
                return $application;
            }
        }
        return null;
    }

    function super_bar($a = '', $b = '')
    {
        $auth = Zend_Auth::getInstance();
        $site = Zend_Registry::get('site');
        $applications = $site->getInstances('application');
        $admin = self::getInstanceByPackageId('admin');
        ?>

        <?php if ($auth->hasIdentity() && isset($admin)) : ?>
            <div class="ld-super-bar-menu" style="display:none">
                <ul>
                    <?php foreach ($admin->getLinks() as $link) : ?>
                        <li><a href="<?php echo $link['href'] ?>"><?php echo $link['title'] ?></a></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div class="h6e-super-bar">
            <div class="h6e-super-bar-inner">
                <div class="a">
                    <?php // to be replaced by something like "isAdmin"
                    if ($auth->hasIdentity() && isset($admin)) {
                        ?>
                        <span class="ld-super-bar-main-menu-button">
                            <a href="<?php echo $admin->getUrl() ?>"> ★ La Distribution admin</a>
                        </span>
                        <?php
                    }
                    foreach ($applications as $id => $application) {
                        if ($application['package'] == 'admin') {
                            continue;
                        }
                        $current = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/' . $application['path'] ) !== false;
                        $settings = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/slotter/instance/manage/id/' . $id ) !== false;
                        if ($current || $settings) echo '<strong>';
                        echo '<a href="' . $site->getUrl() . $application['path'] . '/">' . $application['name'] . '</a>';
                        if ($current || $settings)  echo '</strong>';
                        echo ' &nbsp; ';
                    }
                    ?>
                </div>
                <div class="b">
                    <?php
                    // to be replaced by something like "isAdmin"
                    if ($auth->hasIdentity() && isset($admin)) {
                        foreach ($applications as $id => $application) {
                            $current = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/' . $application['path'] ) !== false ;
                            $settings = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/instance/id/' . $id ) !== false ;
                            if ($current || $settings) {
                                if ($settings) echo '<strong>';
                                echo '<a href="' . $admin->getUrl() . 'slotter/instance/id/' . $id . '">Settings</a>';
                                if ($settings) echo '</strong>';
                                break;
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <script type="text/javascript" src="<?php echo $site->getUrl('js') ?>/jquery/jquery.js"></script>
        
        <script type="text/javascript">
        (function($) {
            $(document).ready(function($){
                $(document).click(
                    function(e){ $('.ld-super-bar-menu').hide(); }
                );
                $('.ld-super-bar-main-menu-button a').click(
                    function(e) { $('.ld-super-bar-menu').toggle(); return false; }
                );
            });
        })(jQuery);
        </script>

        <?php
    }

}
