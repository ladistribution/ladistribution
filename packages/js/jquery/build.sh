VERSION="1.3.2"
FOLDER="js"
NAME="jquery"
SOURCE="http://jqueryjs.googlecode.com/files/$NAME-$VERSION.min.js"
PACKAGE="js-$NAME.zip"
# Get source
mkdir $FOLDER
curl $SOURCE > $FOLDER/$NAME.js
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER