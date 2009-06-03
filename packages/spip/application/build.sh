NAME="spip"
VERSION="2-0-8"
ZIP="$NAME.zip"
SOURCE="http://files.spip.org/spip/archives/SPIP-v$VERSION.zip"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
curl $SOURCE > $ZIP
unzip $ZIP
mv $NAME $FOLDER
rm $ZIP

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist config -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER