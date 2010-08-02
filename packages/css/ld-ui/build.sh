NAME="css-ld-ui"
SOURCE="http://ladistribution.net/svn/trunk/css/ld-ui"
FOLDER="css"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
svn export $SOURCE $FOLDER --force --quiet
# cp -R /Web/ld/css/ld-ui $FOLDER

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml --quiet --exclude \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER