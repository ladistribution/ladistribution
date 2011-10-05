NAME="admin"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Copy 'admin' from filesystem"
cp -R ../../admin $FOLDER

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\* \*.preserve
mv $PACKAGE ..

# Clean
rm -rf $FOLDER
