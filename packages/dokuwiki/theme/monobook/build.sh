NAME="monobook"
VERSION="2010-01-20"
SOURCE="http://www.andreas-haerter.de/public/user/{$VERSION}_{$NAME}.tar.gz"
GZ="$NAME.tgz"
FOLDER="theme"
PACKAGE="dokuwiki-theme-$NAME.zip"
# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv $NAME $FOLDER
cp screenshot.png $FOLDER
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER