NAME="wordpress"
VERSION="2.8"
GZ="$NAME.tar.gz"
SOURCE="http://wordpress.org/$NAME-$VERSION.tar.gz"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv $NAME $FOLDER

# Alternative
# svn export http://svn.automattic.com/wordpress/trunk/ $FOLDER

# Remove some useless (or not desired) files
rm $FOLDER/wp-atom.php
rm $FOLDER/wp-commentsrss2.php
rm $FOLDER/wp-config-sample.php
rm $FOLDER/wp-feed.php
rm $FOLDER/wp-rdf.php
rm $FOLDER/wp-rss.php
rm $FOLDER/wp-rss2.php
rm $FOLDER/wp-register.php
rm $FOLDER/wp-content/plugins/hello.php
rm -rf $FOLDER/wp-content/plugins/akismet

# Remove more files (useless importers)
rm $FOLDER/wp-admin/import/blogware.php
rm $FOLDER/wp-admin/import/btt.php
rm $FOLDER/wp-admin/import/jkw.php
rm $FOLDER/wp-admin/import/greymatter.php
rm $FOLDER/wp-admin/import/stp.php
rm $FOLDER/wp-admin/import/utw.php
rm $FOLDER/wp-admin/import/wp-cat2tag.php

# Apply patches
patch -p0 -d $FOLDER < patches/wp-user-search.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER