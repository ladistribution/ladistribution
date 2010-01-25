NAME="laconica"
VERSION="0.8.2"
GZ="$NAME.gz"
SOURCE="http://status.net/statusnet-$VERSION.tar.gz"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv $NAME-$VERSION $FOLDER

# Remove unecessary files
rm $FOLDER/sphinx.conf.sample
rm $FOLDER/lighttpd.conf.sample
rm $FOLDER/htaccess.sample
rm $FOLDER/config.php.sample
rm $FOLDER/EVENTS.txt
rm -rf $FOLDER/tests
rm -rf $FOLDER/scripts

# Remove Dependencies
rm -rf $FOLDER/extlib/Auth

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER