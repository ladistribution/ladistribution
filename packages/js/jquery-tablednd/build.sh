VERSION="0.5"
FOLDER="js"
NAME="jquery-tablednd"
ZIP="jquery.tablednd_0_5.js.zip"
SOURCE="http://www.isocra.com/articles/$ZIP"
PACKAGE="js-$NAME.zip"
BUILD="build"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $ZIP
unzip -q $ZIP -d $BUILD
rm $ZIP

# Copy the files we really want
mkdir $FOLDER
mkdir $FOLDER/jquery/
cp "$BUILD/jquery.tablednd_0_5.js" "$FOLDER/jquery/jquery.tablednd.js"
rm -rf $BUILD

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml --quiet
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER