NAME="admin"
SOURCE="http://ladistribution.net/svn/trunk/"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
svn export "$SOURCE/admin/" $FOLDER --force --quiet
# Local Export
# cp -R /Web/ld/admin/* $FOLDER

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\* \*.preserve
mv $PACKAGE ..

# Clean
rm -rf $FOLDER