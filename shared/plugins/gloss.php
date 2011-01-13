<?php

class Ld_Plugin_Gloss
{

    public function infos()
    {
        return array(
            'name' => 'Gloss',
            'url' => 'http://ladistribution.net/wiki/plugins/#gloss',
            'author' => 'h6e.net',
            'author_url' => 'http://h6e.net/',
            'version' => '0.5.0',
            'description' => Ld_Translate::translate('A bit of CSS3 bling bling to tune your website.'),
            'license' => 'MIT / GPL'
        );
    }

    const STATUS_OK = 1;
    const STATUS_ERROR = 0;

    public function status()
    {
        return array(self::STATUS_OK, sprintf(Ld_Translate::translate('%s is running.'), 'Gloss'));
    }

    public function load()
    {
        Ld_Plugin::addAction('Slotter:SiteStyle', array($this, 'siteStyle'), 10, 2);
    }

    public function siteStyle($colors, $parts)
    {
        if (!isset($parts) || in_array('base', $parts)) {
            $bg = $colors['ld-colors-background'];
            $lighter = self::contrastColor($bg, -13);
            $darker = self::contrastColor($bg, 13);
            ?>
            /* Base */
            body, .ld-layout, .h6e-layout, body.ld-layout {
                min-height:450px;
                background-repeat: repeat-x;
                background-color:#<?php echo $lighter ?>;
                background-image: -moz-linear-gradient(top, #<?php echo $darker ?>, #<?php echo $lighter ?> 450px);
                background-image: -webkit-gradient(linear, left top, left 450, from(#<?php echo $darker ?>), to(#<?php echo $lighter ?>));
            }
            ul.blocks.mini li, .h6e-block {
                -moz-box-shadow: 5px 5px 5px rgba(0,0,0,0.25);
                -webkit-box-shadow: 5px 5px 5px rgba(0,0,0,0.25);
                box-shadow: 5px 5px 5px rgba(0,0,0,0.25);
            }
            <?php
        }
    }

    public static function contrastColor($color, $diff = 20)
    {
        $rgb = '';
        for ($x=0; $x<3; $x++) {
            $c = hexdec(substr($color,(2*$x),2)) - $diff;
            $c = ($c < 0) ? 0 : ( ($c > 255) ? 'ff' : dechex($c) );
            $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
        }
        return $rgb;
    }

}
