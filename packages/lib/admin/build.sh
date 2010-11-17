NAME="lib-admin"
SOURCE="http://ladistribution.net/svn/trunk/"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"

# Export from SVN
svn export "$SOURCE/js/ld" js --force --quiet
# Local Export
# mkdir js
# cp -R /Web/ld/js/ld js/ld

echo "# Compress JS"
java -jar ../../../bin/yuicompressor-2.4.2.jar --charset UTF-8 "js/ld.js" -o "js/ld.c.js"

# Export from SVN
svn export "$SOURCE/shared/modules" modules --force --quiet
# Local Export
# cp -R /Web/ld/shared/modules modules

# Export from SVN
svn export "$SOURCE/shared/plugins" plugins --force --quiet
# Local Export
# cp -R /Web/ld/shared/plugins plugins

# Remove some plugins
# rm plugins/subsite.php
# rm plugins/user-database.php

mkdir locales
# Export from SVN
svn export "$SOURCE/shared/locales/admin/en_US" locales/en_US --force --quiet
# Local Export
# cp -R /Web/ld/shared/locales/admin/en_US locales/en_US

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE js modules plugins locales manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf js
rm -rf modules
rm -rf plugins
rm -rf locales