NAME="journalist"
SOURCE="http://lucianmarin.com/downloads/$NAME.zip"
FOLDER="theme"
PACKAGE="wordpress-theme-$NAME.zip"
ZIP="$NAME.zip"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP -d $FOLDER
rm $ZIP
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER