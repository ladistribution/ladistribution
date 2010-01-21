SOURCE="http://ladistribution.net/svn/trunk/css/ld-ui"
FOLDER="css"
PACKAGE="css-ld-ui.zip"
# Export from SVN
svn export $SOURCE $FOLDER
# cp -R /Web/ld/css/ld-ui $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve
mv $PACKAGE ../../
#Clean
rm -rf $FOLDER