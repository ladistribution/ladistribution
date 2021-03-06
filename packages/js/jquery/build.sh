NAME="jquery"
VERSION="1.11.0"
FOLDER="js"
SOURCE="http://code.jquery.com/$NAME-$VERSION.min.js"
PACKAGE="js-$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
mkdir $FOLDER
curl $SOURCE -# > $FOLDER/$NAME.js

cp .htaccess $FOLDER

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER