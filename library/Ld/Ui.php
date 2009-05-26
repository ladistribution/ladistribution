<?php

class Ld_Ui
{

    function super_bar($a = '', $b = '')
    {
        ?>
        <div class="h6e-super-bar">
            <div class="h6e-super-bar-inner">
                <div class="a">
                    <span style="border-right:1px solid #999;padding-right:0.5em;margin-right:0.5em">
                        <a href="/h6e/slotter/">
                            ★ Applications
                        </a>
                    </span>
                    <?php
                    $site = Zend_Registry::get('site');
                    $applications = $site->getInstances('application');
                    // $applications['slotter'] = array('name' => 'Slotter', 'path' => 'slotter');
                    foreach ($applications as $id => $application) {
                        $current = strpos( $_SERVER["REQUEST_URI"], LD_BASE_PATH . '/' . $application['path'] ) !== false;
                        $settings = strpos( $_SERVER["REQUEST_URI"], LD_BASE_PATH . '/instance/id/' . $id ) !== false;
                        if ($current || $settings) {
                            echo '<strong><a href="' . LD_BASE_URL . $application['path'] . '/">' . $application['name'] . '</a></strong>';
                        } else {
                            echo '<a href="' . LD_BASE_URL . $application['path'] . '/">' . $application['name'] . '</a>';
                        }
                        echo ' &nbsp; ';
                    }
                    ?>
                    <!-- <a href="#">more ▲</a> -->
                </div>
                <div class="b">
                    <?php
                    foreach ($applications as $id => $application) {
                        $current = strpos( $_SERVER["REQUEST_URI"], LD_BASE_PATH . '/' . $application['path'] ) !== false ;
                        $settings = strpos( $_SERVER["REQUEST_URI"], LD_BASE_PATH . '/instance/id/' . $id ) !== false ;
                        if ($current || $settings) {
                            if ($settings) echo '<strong>';
                            echo '<a href="' . LD_BASE_PATH . '/slotter/instance/id/' . $id . '">Settings</a>';
                            
                            if ($settings) echo '</strong>';
                            break;
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

}
