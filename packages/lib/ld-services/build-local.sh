NAME="lib-ld-services"
FOLDER="lib"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Copy 'lib' from filesystem"
cp -R ../../../lib/Ld/Services $FOLDER

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER locales manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf shared
rm -rf locales
