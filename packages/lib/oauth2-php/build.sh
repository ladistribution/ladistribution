VERSION="6182ed2"
FOLDER="lib"
NAME="oauth2-php"
SOURCE="https://github.com/quizlet/oauth2-php/zipball/$VERSION"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
BUILD="quizlet-oauth2-php-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

# Grab files we want
mv "$BUILD/lib" $FOLDER
rm -rf "$BUILD"

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
