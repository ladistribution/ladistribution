PATH=$PATH:../../bin

SITE="../.."
REPOSITORY="edge/fr"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT admin-locale-fr-fr.zip
$IMPORT ld-locale-fr-fr.zip
$IMPORT dokuwiki-locale-fr-fr.zip
$IMPORT wordpress-locale-fr-fr.zip
$IMPORT bbpress-locale-fr-fr.zip
