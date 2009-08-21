<?php defined("SYSPATH") or die("No direct script access.");

class ld_theme_Core
{

    static function head()
    {
        ?>
        <style type="text/css">
        @import "<?php echo Zend_Registry::get('site')->getUrl('css') ?>/ld-ui/ld-bars.css";
        </style>
        <?php
    }

    static function header_top()
    {
        ?>
        <style type="text/css">
        body, html {
            background:none;
        }
        body, a, a:visited {
            color:#4E5368;
        }
        .gView, #doc4 {
            margin-top:35px;
            border:none;
        }
        #gHeader {
            background:none;
        }
        #gLoginMenu {
            display:none;
        }
        #gFooter {
            background:none;
            padding:10px 0;
            margin-bottom:75px;
        }
        #gCredits {
            font-size:12px;
            background:none;
        }
        #gCredits a {
            color:inherit !important;
            text-decoration:underline;
        }
        </style>
        <?php
    }

    static function footer()
    {
        Ld_Ui::top_bar();
        Ld_Ui::super_bar();
    }

    static function admin_footer()
    {
        Ld_Ui::super_bar(array('style' => true));
    }

}
