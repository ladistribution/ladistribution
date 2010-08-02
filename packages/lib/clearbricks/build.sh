NAME="lib-clearbricks"
SOURCE="https://clearbricks.org/svn/trunk/"
FOLDER="lib"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
svn export -r 248 $SOURCE $FOLDER --quiet

echo "# Apply patches"
patch -p0 -d $FOLDER < patches/empty-directory.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml --quiet --exclude \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER