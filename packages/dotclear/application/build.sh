NAME="dotclear"
VERSION="2.1.7"
ZIP="$NAME-$VERSION.zip"
SOURCE="http://download.dotclear.org/latest/$ZIP"
FOLDER="application"
PACKAGE="$NAME.zip"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP
rm $ZIP
mv $NAME $FOLDER
# Remove clearbricks (dependency)
rm -rf $FOLDER/inc/clearbricks
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER plugins dist -x "*/.svn/*"
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER