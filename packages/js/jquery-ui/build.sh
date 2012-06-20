VERSION="1.8.21"
FOLDER="js"
NAME="jquery-ui"
ZIP="$NAME-$VERSION.custom.zip"
SOURCE="http://jqueryui.com/download/$ZIP"
PACKAGE="js-$NAME.zip"
BUILD="build"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)"  -# > $ZIP

unzip -q $ZIP -d $BUILD
rm $ZIP

# Copy the files we really want
mkdir $FOLDER
cp "$BUILD/js/$NAME-$VERSION.custom.min.js" "$FOLDER/jquery-ui.js"
rm -rf $BUILD

# We don't handle CSS for now (maybe in a separate package)

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
