VERSION="2.1.4"
FOLDER="lib"
NAME="bad-behavior"
SOURCE="http://downloads.wordpress.org/plugin/$NAME.$VERSION.zip"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP
rm $ZIP
# Grab files we want
mv $NAME/$NAME $FOLDER
rm -rf $NAME
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER
