NAME="monobook"
SOURCE="http://tjgrant.com/files/dokuwiki/$NAME-current.tar.bz2"
FOLDER="theme"
PACKAGE="dokuwiki-theme-$NAME.zip"
TAR="$NAME.tar.bz2"
# Get source
curl $SOURCE > $TAR
tar xvjf $TAR
rm $TAR
mv $NAME $FOLDER
cp screenshot.png $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER