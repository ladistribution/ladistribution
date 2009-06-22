NAME="bmpress-minimal"
VERSION="0.1"
SOURCE="http://code.bmpress.org/svn/trunk/themes/$NAME"
FOLDER="theme"
PACKAGE="wordpress-theme-$NAME.zip"
# Get source
svn export $SOURCE $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER