VERSION="dafef11"
FOLDER="lib"
NAME="facebook-facebook-php-sdk"
SOURCE="https://github.com/facebook/facebook-php-sdk/zipball/$VERSION"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
TMP="$NAME-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -kL $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

echo "# Copy files"
mv "$TMP/src" $FOLDER
rm -rf $TMP

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
