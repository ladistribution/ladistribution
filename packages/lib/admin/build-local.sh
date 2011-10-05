NAME="lib-admin"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Copy 'js' from filesystem"
cp -R ../../../js/ld js

echo "# Compress JS"
java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "js/ld.js" -o "js/ld.c.js"

echo "# Copy 'modules' from filesystem"
cp -R ../../../shared/modules modules

echo "# Copy 'plugins' from filesystem"
cp -R ../../../shared/plugins plugins

# Remove some plugins
rm plugins/bad-behavior.php
rm plugins/bouncer.php

echo "# Copy 'locales' from filesystem"
mkdir locales
cp -R ../../../shared/locales/admin/en_US locales/en_US

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
