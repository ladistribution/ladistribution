NAME="simpla"
SOURCE="http://ifelse.co.uk/download/$NAME.zip"
FOLDER="theme"
PACKAGE="wordpress-theme-$NAME.zip"
ZIP="$NAME.zip"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP
rm $ZIP
mv $NAME $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER