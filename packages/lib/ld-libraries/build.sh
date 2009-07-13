SOURCE="http://ladistribution.net/svn/trunk/lib/"
FOLDER="lib"
PACKAGE="lib-ld.zip"
# Export from SVN
svn export $SOURCE $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml -x "*/.svn/*"
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER