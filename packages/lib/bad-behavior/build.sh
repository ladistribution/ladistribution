VERSION="2.1.7"
FOLDER="lib"
NAME="bad-behavior"
SOURCE="http://downloads.wordpress.org/plugin/$NAME.$VERSION.zip"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
rm $ZIP

# Grab files we want
mv $NAME/$NAME $FOLDER
rm -rf $NAME

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml --quiet
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
