NAME="css-h6e-minimal"
SOURCE="git://github.com/znarf/h6e-minimal.git"
FOLDER="css"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE $FOLDER --quiet
rm -rf $FOLDER/.git

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER