VERSION="0.5.6"
FOLDER="lib"
NAME="rediska"
# SOURCE="http://rediska.geometria-lab.ru/download/Rediska-0-5-1.zip"
SOURCE="https://github.com/Shumkov/Rediska/zipball/release-0-5-6"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
BUILD="Shumkov-Rediska-497afe5"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

# Grab files we want
mv "$BUILD/library/Rediska" $FOLDER
mv "$BUILD/library/Rediska.php" Rediska.php
rm -rf "$BUILD"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml Rediska.php -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER Rediska.php
