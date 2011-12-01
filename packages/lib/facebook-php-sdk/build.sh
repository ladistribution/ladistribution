VERSION="dafef11"
FOLDER="lib"
NAME="facebook-php-sdk"
SOURCE="https://github.com/facebook/php-sdk/zipball/$VERSION"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
BUILD="$NAME-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

# Grab files we want
mv "$BUILD/src" $FOLDER
rm -rf $BUILD

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
