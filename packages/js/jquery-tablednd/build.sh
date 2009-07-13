VERSION="0.5"
FOLDER="js"
NAME="jquery-tablednd"
ZIP="jquery.tablednd_0_5.js.zip"
SOURCE="http://www.isocra.com/articles/$ZIP"
PACKAGE="js-$NAME.zip"
BUILD="build"

# Get source
curl $SOURCE > $ZIP
unzip $ZIP -d $BUILD
rm $ZIP

# Copy the files we really want
mkdir $FOLDER
mkdir $FOLDER/jquery/
cp "$BUILD/jquery.tablednd_0_5.js" "$FOLDER/jquery/jquery.tablednd.js"
rm -rf $BUILD

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER