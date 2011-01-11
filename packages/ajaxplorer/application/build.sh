NAME="ajaxplorer"
VERSION="3.2"
ZIP="$NAME-core-$VERSION.zip"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

# Get source ZIP
SOURCE="http://freefr.dl.sourceforge.net/project/$NAME/$NAME/$VERSION/$ZIP"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
mv "$NAME-core-$VERSION" $FOLDER
rm $ZIP

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -rqv $PACKAGE $FOLDER dist conf plugins -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER