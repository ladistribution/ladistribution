PATH=$PATH:../../bin

SITE="../.."
REPOSITORY="edge/de"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT admin-locale-de-de.zip
$IMPORT ld-locale-de-de.zip
$IMPORT dokuwiki-locale-de-de.zip
$IMPORT wordpress-locale-de-de.zip
$IMPORT bbpress-locale-de-de.zip
$IMPORT statusnet-locale-de-de.zip
