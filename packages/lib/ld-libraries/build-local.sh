NAME="lib-ld"
FOLDER="lib"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Copy 'lib' from filesystem"
cp -R ../../../lib/Ld $FOLDER

echo "# Copy 'locales' from filesystem"
cp -R ../../../shared/locales/ld locales

rm -rf locales/fr_FR
rm -rf locales/de_DE

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER locales manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf shared
rm -rf locales
