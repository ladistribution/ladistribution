FOLDER="lib"
NAME="bouncer"
SOURCE="git://github.com/znarf/Bouncer.git"
ZIP="$NAME.zip"
PACKAGE="lib-bouncer.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE $FOLDER --quiet
rm -rf $FOLDER/.git

mv $FOLDER/images images

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER images manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER images
