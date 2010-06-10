VERSION="1.8.2"
FOLDER="js"
NAME="jquery-ui"
ZIP="$NAME-$VERSION.custom.zip"
SOURCE="http://jqueryui.com/download/$ZIP"
PACKAGE="js-$NAME.zip"
BUILD="build"

# Get source
curl $SOURCE > $ZIP
unzip $ZIP -d $BUILD
rm $ZIP

# Copy the files we really want
mkdir $FOLDER
cp "$BUILD/js/$NAME-$VERSION.custom.min.js" "$FOLDER/jquery-ui.js"
rm -rf $BUILD

# We don't handle CSS for now (maybe in a separate package)

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
