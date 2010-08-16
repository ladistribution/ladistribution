NAME="lib-ld"
SOURCE="http://ladistribution.net/svn/trunk/lib/Ld"
FOLDER="lib"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
svn export $SOURCE $FOLDER --quiet
# Local Export
# mkdir $FOLDER
# cp -R /Web/ld/lib/Ld $FOLDER

# Get locales

# Export from SVN
svn export "http://ladistribution.net/svn/trunk/shared/locales/ld" locales --quiet
rm -rf locales/fr_FR

# we have to do that this way, because of limitations in old version of Ld Libraries
# mkdir shared
# mkdir shared/locales
# mkdir locales
# svn export "http://ladistribution.net/svn/trunk/shared/locales/ld/en_US" locales/en_US

# Local Export
# cp -R /Web/ld/shared/locales/ld/en_US shared/locales/ld/en_US

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER locales manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf shared
rm -rf locales