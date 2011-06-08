NAME="dokuwiki"
VERSION="2011-05-25"
SUB_VERSION=""
GZ="$NAME.tgz"
SOURCE="http://www.splitbrain.org/_media/projects/$NAME/$NAME-{$VERSION}{$SUB_VERSION}.tgz"
FOLDER="locale"
PACKAGE="$NAME-locale-de-de.zip"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $GZ
tar -x -f $GZ
rm $GZ
mv "$NAME-$VERSION/inc/lang/de" $FOLDER
rm -rf "$NAME-$VERSION"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE dist $FOLDER -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER