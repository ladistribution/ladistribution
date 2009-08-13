VERSION="4.0.0"
FOLDER="lib"
NAME="htmlpurifier-$VERSION-lite"
SOURCE="http://htmlpurifier.org/releases/$NAME.zip"
ZIP="$NAME.ZIP"
PACKAGE="lib-htmlpurifier.zip"

# Get source
curl $SOURCE > $ZIP
unzip $ZIP
rm $ZIP

# Copy files
cp -R "$NAME/library" $FOLDER
rm -rf $NAME

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER