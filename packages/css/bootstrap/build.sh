NAME="css-bootstrap"
VERSION="70b1a6b"
SOURCE="https://github.com/twitter/bootstrap/zipball/$VERSION"
FOLDER="css"
PACKAGE="$NAME.zip"
GZ="bootstrap.gz"
TMP="twitter-bootstrap-$VERSION"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $GZ
tar -xf $GZ
rm $GZ

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
