NAME="laconica"
VERSION="0.7.3"
GZ="$NAME.gz"
SOURCE="http://laconi.ca/$NAME-$VERSION.tar.gz"
FOLDER="application"
PACKAGE="$NAME.zip"

echo $SOURCE

# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv $NAME-$VERSION $FOLDER

# Dependencies
rm -rf $FOLDER/extlib/Auth

# Remove some unwanted files (osX)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER