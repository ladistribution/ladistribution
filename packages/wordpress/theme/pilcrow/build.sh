NAME="pilcrow"
VERSION="1.0"
SOURCE="http://wordpress.org/extend/themes/download/$NAME.$VERSION.zip"
FOLDER="theme"
BUILD="build"
PACKAGE="wordpress-theme-$NAME.zip"
ZIP="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -# $SOURCE > $ZIP
unzip -q $ZIP -d $BUILD
mv $BUILD/$NAME $FOLDER
rmdir $BUILD
rm $ZIP

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../../

# Clean
rm -rf $FOLDER