SOURCE="http://ladistribution.h6e.net/svn/trunk/library/"
FOLDER="lib"
PACKAGE="lib-ld.zip"
# Export from SVN
svn export $SOURCE $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE lib
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER