NAME="habari"
VERSION="0.6.2"
ZIP="$NAME-$VERSION.zip"
SOURCE="http://dist.habariproject.org/$ZIP"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
curl $SOURCE > $ZIP
unzip $ZIP
mv "$NAME-$VERSION" $FOLDER
rm $ZIP

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
