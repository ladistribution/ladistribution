NAME="bbpress-theme-minimal"
SOURCE="git://github.com/znarf/bbpress-minimal.git"
FOLDER="theme"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE"
git clone $SOURCE $FOLDER --quiet
rm -rf $FOLDER/.git

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q -x \*.svn/\*
mv $PACKAGE ../../../

# Clean
rm -rf $FOLDER