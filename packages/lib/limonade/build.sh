VERSION="0.5"
FOLDER="lib"
NAME="limonade"
SOURCE="git://github.com/sofadesign/limonade.git"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
BUILD="build"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone -q git://github.com/sofadesign/limonade.git $BUILD
cd $BUILD
git checkout -q origin/0.5-stable
cd ..

# Grab files we want
mv "$BUILD/lib/limonade" $FOLDER
mv "$BUILD/lib/limonade.php" limonade.php
rm -rf "$BUILD"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml limonade.php
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER limonade.php
