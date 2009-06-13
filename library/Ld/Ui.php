<?php

class Ld_Ui
{

    public static function getInstanceByPackageId($packageId, $instances = array())
    {
        foreach ($instances as $instance) {
            if ($instance->getPackageId() == $packageId) {
                return $instance;
            }
        }
        return null;
    }

    public static function super_bar($params = array())
    {
        $auth = Zend_Auth::getInstance();
        $site = Zend_Registry::get('site');

        $applications = array();
        foreach ($site->getInstances('application') as $id => $infos) {
            $applications[$id] = $site->getInstance($id);
        }

        $admin = self::getInstanceByPackageId('admin', $applications);

        $isAdmin = false;
        if ($auth->hasIdentity() && isset($admin)) {
            $username = $auth->getIdentity();
            $userRoles = $admin->getUserRoles();
            if (isset($userRoles[$username]) && $userRoles[$username] == 'admin') {
                $isAdmin = true;
            }
        } else {
            $users = $site->getUsers();
            if (empty($users)) {
                $isAdmin = true;
            }
        }
        ?>

        <?php if ($isAdmin) : ?>
            <div class="ld-super-bar-menu" style="display:none">
                <ul>
                    <?php foreach ($admin->getLinks() as $link) : ?>
                        <li><a href="<?php echo $link['href'] ?>"><?php echo $link['title'] ?></a></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <div id="ld-super-bar" class="h6e-super-bar">
            <div class="h6e-super-bar-inner">
                <div class="a">
                    <?php if ($isAdmin) { ?>
                        <span class="ld-super-bar-main-menu-button">
                            <a href="<?php echo $admin->getUrl() ?>"> ★ <?php echo $admin->getName() ?></a>
                        </span>
                    <?php }
                    foreach ($applications as $id => $application) {
                        if ($application->getPackageId() == 'admin') {
                            continue;
                        }
                        $current = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/' . $application->getPath() ) === 0;
                        if ($current) echo '<strong>';
                        echo '<a href="' . $site->getUrl() . $application->getPath() . '/">' . $application->getName() . '</a>';
                        if ($current)  echo '</strong>';
                    }
                    ?>
                </div>
                <div class="b">
                    <?php if ($isAdmin) {
                        foreach ($applications as $id => $application) {
                            $current = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/' . $application->getPath() ) === 0;
                            $settings = strpos( $_SERVER["REQUEST_URI"], 'slotter/instance/id/' . $id ) !== false ;
                            if ($current || $settings) {
                                if ($settings) echo '<strong>';
                                echo '<a href="' . $admin->getUrl() . 'slotter/instance/id/' . $id . '">◆ Settings</a>';
                                if ($settings) echo '</strong>';
                                break;
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if (!isset($params['jquery']) || $params['jquery'] === true ) : ?>
            <script type="text/javascript" src="<?php echo $site->getUrl('js') ?>/jquery/jquery.js"></script>
        <?php endif ?>

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
