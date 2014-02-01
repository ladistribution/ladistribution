VERSION="1.10.4"
FOLDER="js"
NAME="jquery-ui"
ZIP="$NAME-$VERSION.zip"
SOURCE="http://code.jquery.com/ui/$VERSION/jquery-ui.min.js"
PACKAGE="js-$NAME.zip"

echo "# Building $NAME package"

mkdir $FOLDER

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > "$FOLDER/jquery-ui.js"

# We don't handle CSS for now (maybe in a separate package)

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
