VERSION="4.4.0"
FOLDER="lib"
NAME="htmlpurifier-$VERSION-lite"
SOURCE="http://htmlpurifier.org/releases/$NAME.zip"
ZIP="$NAME.ZIP"
PACKAGE="lib-htmlpurifier.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
rm $ZIP

# Copy files
cp -R "$NAME/library" $FOLDER
rm -rf $NAME

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER