NAME="p2"
VERSION="1.1.3"
SOURCE="http://wordpress.org/extend/themes/download/$NAME.$VERSION.zip"
FOLDER="theme"
PACKAGE="wordpress-theme-$NAME.zip"
ZIP="$NAME.zip"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP
mv $NAME $FOLDER
rm $ZIP
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER