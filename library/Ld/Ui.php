<?php

class Ld_Ui
{

    function super_bar($a = '', $b = '')
    {
        $auth = Zend_Auth::getInstance();
        $site = Zend_Registry::get('site');
        $applications = $site->getInstances('application');
        $adminUrl = $site->getInstance('admin')->getUrl();
        ?>
        <div class="h6e-super-bar">
            <div class="h6e-super-bar-inner">
                <div class="a">
                    <?php // to be replaced by something like "isAdmin"
                    if ($auth->hasIdentity()) {
                    ?>
                        <span style="border-right:1px solid #999;padding-right:0.5em;margin-right:0.5em">
                            <a href="<?php echo $adminUrl ?>slotter/">
                                ★ Applications
                            </a>
                        </span>
                    <?php
                    }
                    
                    foreach ($applications as $id => $application) {
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
                    if ($auth->hasIdentity()) {
                        foreach ($applications as $id => $application) {
                            $current = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/' . $application['path'] ) !== false ;
                            $settings = strpos( $_SERVER["REQUEST_URI"], $site->getPath() . '/instance/id/' . $id ) !== false ;
                            if ($current || $settings) {
                                if ($settings) echo '<strong>';
                                echo '<a href="' . $adminUrl . 'slotter/instance/id/' . $id . '">Settings</a>';
                                if ($settings) echo '</strong>';
                                break;
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

}
