NAME="wordpress"
VERSION="2.7.1"
GZ="$NAME.tar.gz"
SOURCE="http://wordpress.org/$NAME-$VERSION.tar.gz"
FOLDER="application"
PACKAGE="$NAME.zip"
# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv $NAME $FOLDER
# Remove some useless (or not desired) files
rm $FOLDER/wp-atom.php
rm $FOLDER/wp-commentsrss2.php
rm $FOLDER/wp-config-sample.php
rm $FOLDER/wp-feed.php
rm $FOLDER/wp-rdf.php
rm $FOLDER/wp-rss.php
rm $FOLDER/wp-rss2.php
rm $FOLDER/wp-content/plugins/hello.php
rm -rf $FOLDER/wp-content/plugins/akismet
# Apply patches
patch -p0 -d $FOLDER < patches/wp-user-search.diff
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins -x "*/.svn/*"
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER