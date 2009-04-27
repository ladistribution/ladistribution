<?php

class Ld_Ui
{

    function super_bar($a = '', $b = '')
    {
        ?>
        <div class="h6e-super-bar">
            <div class="h6e-super-bar-inner">
                <div class="a">
                    <?php
                    require_once 'Ld/Site/Local.php';
                    $site = new Ld_Site_Local();
                    $applications = $site->getInstances('application');
                    $applications['slotter'] = array('name' => 'Slotter', 'path' => 'slotter');
                    foreach ($applications as $id => $application) {
                        $current = strpos( $_SERVER["REQUEST_URI"], LD_BASE_PATH . '/' . $application['path'] ) !== false;
                        $settings = strpos( $_SERVER["REQUEST_URI"], LD_BASE_PATH . '/admin/' . $application['path'] ) !== false;
                        if ($current || $settings) {
                            echo '<strong><a href="' . LD_BASE_URL . $application['path'] . '/">' . $application['name'] . '</a></strong>';
                        } else {
                            echo '<a href="' . LD_BASE_URL . $application['path'] . '/">' . $application['name'] . '</a>';
                        }
                        echo ' &nbsp; ';
                    }
                    ?>
                    <a href="#">more ▲</a>
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
