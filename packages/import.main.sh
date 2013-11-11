PATH=$PATH:../bin

SITE=".."
REPOSITORY="edge/main"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT admin.zip

$IMPORT lib-zend-framework.zip
$IMPORT lib-clearbricks.zip
$IMPORT lib-ld.zip
$IMPORT lib-ld-services.zip
$IMPORT lib-admin.zip
$IMPORT lib-php-openid.zip
$IMPORT lib-geshi.zip
$IMPORT lib-htmlpurifier.zip
$IMPORT lib-bad-behavior.zip
$IMPORT lib-limonade.zip
$IMPORT lib-rediska.zip
$IMPORT lib-bouncer.zip
$IMPORT lib-sabredav.zip
$IMPORT lib-oauth2-php.zip
$IMPORT lib-google-api-php-client.zip
$IMPORT lib-facebook-php-sdk.zip

$IMPORT plugin-bouncer.zip
$IMPORT plugin-services.zip

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

$IMPORT moonmoon.zip
