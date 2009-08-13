<?php defined("SYSPATH") or die("No direct script access.");

class ld_theme_Core
{

    static function footer()
    {
        Ld_Ui::super_bar(array('style' => true, 'jquery' => false));
        return '<style type="text/css">#gFooter { margin-bottom:75px; } </style>';
    }

    static function admin_footer()
    {
        Ld_Ui::super_bar(array('style' => true, 'jquery' => false));
        return '<style type="text/css">#gFooter { margin-bottom:75px; } </style>';
    }

}
