SOURCE="http://ladistribution.h6e.net/svn/trunk/css/ld-ui"
FOLDER="css"
PACKAGE="css-ld-ui.zip"
# Export from SVN
svn export $SOURCE $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml -x "*/.svn/*"
mv $PACKAGE ../../
#Clean
rm -rf $FOLDER