NAME="dokuwiki"
VERSION="2009-02-14"
GZ="$NAME.tgz"
SOURCE="http://www.splitbrain.org/_media/projects/$NAME/$NAME-{$VERSION}b.tgz"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv "$NAME-$VERSION/inc/lang/fr" $FOLDER
rm -rf "$NAME-$VERSION"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE dist $FOLDER -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER