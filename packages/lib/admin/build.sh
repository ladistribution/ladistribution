NAME="lib-admin"
# SOURCE="http://ladistribution.net/svn/trunk/"
SOURCE="git@github.com:ladistribution/ladistribution.git"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

# echo "# Get source from $SOURCE with svn"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

# Export from SVN
# svn export "$SOURCE/js/ld" js --force --quiet
# Copy from Git
cp -R tmp/js/ld js
# Local Export
# mkdir js
# cp -R /Web/ld/js/ld js/ld

echo "# Compress JS"
java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "js/ld.js" -o "js/ld.c.js"

# Export from SVN
# svn export "$SOURCE/shared/modules" modules --force --quiet
# Copy from Git
cp -R tmp/shared/modules modules
# Local Export
# cp -R /Web/ld/shared/modules modules

# Export from SVN
# svn export "$SOURCE/shared/plugins" plugins --force --quiet
# Copy from Git
cp -R tmp/shared/plugins plugins
# Local Export
# cp -R /Web/ld/shared/plugins plugins

# Remove some plugins
rm plugins/bouncer.php

# Export from SVN
# svn export "$SOURCE/shared/locales/admin/en_US" locales/en_US --force --quiet
# Copy from Git
mkdir locales
cp -R tmp/shared/locales/admin/en_US locales/en_US
# Local Export
# cp -R /Web/ld/shared/locales/admin/en_US locales/en_US

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