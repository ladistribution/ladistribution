SOURCE="http://ladistribution.h6e.net/svn/trunk/admin/"
FOLDER="application"
PACKAGE="admin.zip"
# Export from SVN
svn export $SOURCE $FOLDER
# Move dist directory
mv $FOLDER/dist dist
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist
mv $PACKAGE ../
# Clean
rm -rf $FOLDER dist