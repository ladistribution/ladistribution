PATH=$PATH:../bin

SITE=".."
REPOSITORY="contrib"

IMPORT="ladis import-package --site $SITE $REPOSITORY"

$IMPORT habari.zip
$IMPORT dotclear.zip
$IMPORT laconica.zip
$IMPORT spip.zip
$IMPORT indexhibit.zip
$IMPORT moonmoon.zip
$IMPORT gallery.zip
$IMPORT roundcube.zip

$IMPORT wordpress-plugin-codecolorer.zip
