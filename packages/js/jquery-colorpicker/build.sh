FOLDER="js"
NAME="jquery-colorpicker"
ZIP="colorpicker.zip"
SOURCE="http://www.eyecon.ro/colorpicker/$ZIP"
PACKAGE="js-$NAME.zip"
BUILD="build"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP -d $BUILD
rm $ZIP

# JS
mkdir $FOLDER
cp "$BUILD/js/colorpicker.js" "$FOLDER/colorpicker.js"

# CSS
mkdir css
mkdir css/colorpicker
cp -R "$BUILD/css" "css/css"
cp -R "$BUILD/images" "css/images"

rm css/images/Thumbs.db

rm -rf $BUILD

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER css manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf css
