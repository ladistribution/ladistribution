VERSION="497afe5"
FOLDER="lib"
NAME="rediska"
SOURCE="https://github.com/Shumkov/Rediska/zipball/$VERSION"
ZIP="$NAME.zip"
PACKAGE="lib-$NAME.zip"
TMP="Shumkov-Rediska-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -kL $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

echo "# Copy files"
mv "$TMP/library/Rediska" $FOLDER
mv "$TMP/library/Rediska.php" Rediska.php
rm -rf "$TMP"

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml Rediska.php -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER Rediska.php
