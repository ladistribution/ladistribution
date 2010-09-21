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

    static function admin_head()
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
        .g-view, #doc4 {
            width:978px;
            margin-top:35px;
            border:none;
        }
        #g-header {
            background:none;
        }
        #g-banner {
            background:none;
        }
        #g-login-menu {
            display:none;
        }
        #g-footer {
            background:none;
            padding:10px 0;
            margin-bottom:75px;
        }
        #g-credits {
            font-size:12px;
            background:none;
        }
        #g-credits a {
            color:inherit !important;
            text-decoration:underline;
        }
        .g-inline li {
            padding-left:inherit !important;
        }
        </style>
        <?php
    }

    static function admin_header_top()
    {
        ?>
        <style type="text/css">
        body, html, #g-header {
            background-color:transparent;
        }
        #g-login-menu {
            display:none;
        }
        #g-add-dashboard-block-form select {
            font-size:0.85em;
        }
        #doc3 {
            margin:40px auto 0;
            border: 0;
            width:978px;
        }
        #g-footer {
            display:none;
        }
        </style>
        <?php
    }

    static function page_bottom()
    {
        Ld_Ui::top_bar();
        Ld_Ui::super_bar();
    }

    static function admin_page_bottom()
    {
        Ld_Ui::top_bar();
        Ld_Ui::super_bar();
    }

}
