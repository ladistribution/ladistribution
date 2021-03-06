FOLDER="js"
NAME="codemirror"
VERSION="1.0"
ZIP="$NAME-$VERSION.zip"
SOURCE="http://codemirror.net/$ZIP"
PACKAGE="js-$NAME.zip"
BUILD="CodeMirror-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

# JS
mv $BUILD/js $FOLDER

# echo "# Compressing"
# java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "$FOLDER/codemirror.js" -o "$FOLDER/codemirror.js"

# CSS
mv $BUILD/css css

rm css/baboon.png
rm css/font.js
rm css/people.jpg

rm -rf $BUILD

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER css manifest.xml
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf css
