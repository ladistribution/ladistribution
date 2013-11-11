NAME="lib-ld-services"
SOURCE="git://github.com/ladistribution/ladistribution.git"
FOLDER="lib"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

echo "# Copy 'lib' from tmp"
cp -R tmp/lib/Ld/Services $FOLDER

# Clean tmp
rm -rf tmp

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER locales manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf shared
rm -rf locales
