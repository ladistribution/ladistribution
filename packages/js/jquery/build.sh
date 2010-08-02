NAME="jquery"
VERSION="1.4.2"
FOLDER="js"
SOURCE="http://code.jquery.com/$NAME-$VERSION.min.js"
PACKAGE="js-$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
mkdir $FOLDER
curl $SOURCE -# > $FOLDER/$NAME.js

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml --quiet
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER