PATH=$PATH:../bin

SITE=".."
REPOSITORY="edge/main"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT admin.zip

$IMPORT lib-zend-framework.zip
$IMPORT lib-clearbricks.zip
$IMPORT lib-ld.zip
$IMPORT lib-admin.zip
$IMPORT lib-php-openid.zip
$IMPORT lib-geshi.zip
$IMPORT lib-htmlpurifier.zip
$IMPORT lib-bad-behavior.zip
$IMPORT lib-limonade.zip
$IMPORT lib-rediska.zip
$IMPORT lib-bouncer.zip

$IMPORT plugin-bouncer.zip

$IMPORT css-h6e-minimal.zip
$IMPORT css-ld-ui.zip

$IMPORT js-jquery.zip
$IMPORT js-jquery-ui.zip
$IMPORT js-jquery-tablednd.zip
$IMPORT js-jquery-colorpicker.zip
$IMPORT js-codemirror.zip

$IMPORT wordpress.zip
$IMPORT wordpress-theme-coraline.zip
$IMPORT wordpress-theme-pilcrow.zip

$IMPORT dokuwiki.zip

$IMPORT bbpress.zip

$IMPORT firefox-sync.zip

$IMPORT statusnet.zip
$IMPORT statusnet-plugin-ostatus.zip

$IMPORT moonmoon.zip
