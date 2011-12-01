VERSION="0.4.7"
FOLDER="lib"
NAME="google-api-php-client"
SOURCE="http://$NAME.googlecode.com/files/$NAME-$VERSION.tar.gz"
ARCHIVE="$NAME.tar.gz"
PACKAGE="lib-$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl -L $SOURCE --user-agent "La Distribution Build system (http://ladistribution.net/)" -# > $ARCHIVE
tar -xf $ARCHIVE
rm $ARCHIVE

# Grab files we want
mv $NAME/src $FOLDER
rm -rf $NAME

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
