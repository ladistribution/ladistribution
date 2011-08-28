VERSION="1.5.1"
FOLDER="lib"
NAME="sabredav"
SOURCE="http://sabredav.googlecode.com/files/SabreDAV-$VERSION.zip"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
BUILD="SabreDAV"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

# Grab files we want
mv "$BUILD/lib/Sabre" $FOLDER
# mv "$BUILD/lib/Sabre.includes.php" Sabre.includes.php
rm -rf "$BUILD"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER Sabre.includes.php
