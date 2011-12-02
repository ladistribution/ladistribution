NAME="lib-admin"
SOURCE="git://github.com/ladistribution/ladistribution.git"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

echo "# Copy 'js' from tmp"
cp -R tmp/js/ld js

echo "# Compress JS"
java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "js/ld.js" -o "js/ld.c.js"

echo "# Copy 'modules' from tmp"
cp -R tmp/shared/modules modules

echo "# Copy 'plugins' from tmp"
cp -R tmp/shared/plugins plugins

# Remove some plugins
rm plugins/bad-behavior.php
rm plugins/bouncer.php
rm plugins/gloss.php
rm -rf plugins/services

echo "# Copy 'locales' from tmp"
mkdir locales
cp -R tmp/shared/locales/admin/en_US locales/en_US

# Clean tmp
rm -rf tmp

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
