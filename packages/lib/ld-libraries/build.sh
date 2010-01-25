SOURCE="http://ladistribution.net/svn/trunk/lib/"
FOLDER="lib"
PACKAGE="lib-ld.zip"

# Export from SVN
# svn export $SOURCE $FOLDER
# Local Export
mkdir $FOLDER
cp -R /Web/ld/lib/Ld $FOLDER

# Get locales

# svn export "http://ladistribution.net/svn/trunk/shared/locales/ld" locales
# we have to do that this way, because of limitations in old version of Ld Libraries
mkdir shared
mkdir shared/locales
mkdir shared/locales/ld
# Export from SVN
# svn export "http://ladistribution.net/svn/trunk/shared/locales/ld/en_US" shared/locales/ld/en_US
# Local Export
cp -R /Web/ld/shared/locales/ld/en_US shared/locales/ld/en_US

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER shared manifest.xml -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf shared
rm -rf locales