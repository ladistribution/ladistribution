PATH=$PATH:../bin

SITE=".."
REPOSITORY="edge/main"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT admin.zip

$IMPORT lib-zend-framework.zip
$IMPORT lib-clearbricks.zip
$IMPORT lib-ld.zip
$IMPORT lib-php-openid.zip
$IMPORT lib-geshi.zip
$IMPORT lib-htmlpurifier.zip
$IMPORT lib-bad-behavior.zip

$IMPORT css-h6e-minimal.zip
$IMPORT css-ld-ui.zip

$IMPORT js-jquery.zip
$IMPORT js-jquery-ui.zip
$IMPORT js-jquery-tablednd.zip

$IMPORT wordpress.zip

$IMPORT dokuwiki.zip

$IMPORT bbpress.zip
$IMPORT bbpress-theme-minimal.zip

$IMPORT firefox-sync.zip

$IMPORT statusnet.zip