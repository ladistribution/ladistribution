VERSION="0.7"
FOLDER="js"
NAME="jquery-tablednd"
SOURCE="https://raw.github.com/isocra/TableDnD/70790e6a6e4d17d2a8ba247065bcb6fc2c49462a/js/jquery.tablednd.js"
PACKAGE="js-$NAME.zip"
BUILD="build"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
mkdir $FOLDER
curl $SOURCE -# > $FOLDER/tablednd.js

echo "# Compressing"
java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "$FOLDER/tablednd.js" -o "$FOLDER/tablednd.js"

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER