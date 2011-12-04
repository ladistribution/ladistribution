VERSION="70b1a6b"
FOLDER="css"
NAME="bootstrap"
SOURCE="https://github.com/twitter/bootstrap/zipball/$VERSION"
ZIP="$NAME.zip"
PACKAGE="css-$NAME.zip"
TMP="twitter-bootstrap-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -kL $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ZIP
unzip -q $ZIP
rm $ZIP

echo "# Copy files"
mkdir $FOLDER
mv $TMP/bootstrap.css $FOLDER
mv $TMP/bootstrap.min.css $FOLDER
mv $TMP/js ./
rm -rf js/tests
rm -rf $TMP

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER js manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf js
