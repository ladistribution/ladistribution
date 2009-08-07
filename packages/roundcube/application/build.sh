NAME="roundcube"
VERSION="v0.3-rc1"
SOURCE="https://svn.roundcube.net/tags/roundcubemail/$VERSION/"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
svn export $SOURCE $FOLDER --force

cp $FOLDER/config/main.inc.php.dist dist/default.inc.php

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins skins -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER