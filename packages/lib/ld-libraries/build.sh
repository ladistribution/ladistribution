NAME="lib-ld"
SOURCE="git://github.com/ladistribution/ladistribution.git"
FOLDER="lib"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

echo "# Copy 'lib' from tmp"
cp -R tmp/lib/Ld $FOLDER

rm -rf $FOLDER/Services

echo "# Copy 'locales' from tmp"
cp -R tmp/shared/locales/ld locales

rm -rf locales/fr_FR
rm -rf locales/de_DE

# Clean tmp
rm -rf tmp

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER locales manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf shared
rm -rf locales
