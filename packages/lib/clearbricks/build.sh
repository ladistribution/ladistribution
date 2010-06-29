SOURCE="https://clearbricks.org/svn/trunk/"
FOLDER="lib"
PACKAGE="lib-clearbricks.zip"
# Export from SVN
svn export -r 248 $SOURCE $FOLDER
# Apply patches
patch -p0 -d $FOLDER < patches/empty-directory.diff
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml -x "*/.svn/*"
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER