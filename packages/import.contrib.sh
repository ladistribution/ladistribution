PATH=$PATH:../bin

SITE=".."
REPOSITORY="edge/contrib"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT habari.zip
$IMPORT dotclear.zip
$IMPORT spip.zip
$IMPORT indexhibit.zip
$IMPORT gallery.zip
$IMPORT roundcube.zip
$IMPORT ajaxplorer.zip

$IMPORT wordpress-theme-p2.zip
$IMPORT wordpress-theme-hemingway.zip
$IMPORT wordpress-theme-journalist.zip
$IMPORT wordpress-theme-simpla.zip
$IMPORT wordpress-theme-whiteasmilk.zip

$IMPORT wordpress-plugin-codecolorer.zip
$IMPORT wordpress-plugin-qtranslate.zip
$IMPORT wordpress-plugin-supercache.zip
$IMPORT wordpress-plugin-hypercache.zip

$IMPORT bbpress-theme-minimal.zip

$IMPORT dokuwiki-theme-monobook.zip
