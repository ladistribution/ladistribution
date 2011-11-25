NAME="wordpress"
VERSION="3.3-beta4"
GZ="$NAME-$VERSION.tar.gz"
SOURCE="http://wordpress.org/$GZ"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

# SOURCE="http://core.svn.wordpress.org/trunk/"
# echo "# Get source from $SOURCE with svn"
# svn export $SOURCE $FOLDER --force --quiet

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $GZ
tar -x -f $GZ
rm $GZ
mv $NAME $FOLDER

# Remove some useless (or not desired) files

rm $FOLDER/wp-config-sample.php
rm $FOLDER/wp-register.php
rm $FOLDER/wp-content/plugins/hello.php

echo "# Get minimal theme with git"
git clone git://github.com/znarf/wordpress-minimal.git themes/minimal --quiet
rm -rf themes/minimal/.git themes/minimal/Makefile

echo "# Get akismet with svn"
svn export http://plugins.svn.wordpress.org/akismet/trunk/ plugins/akismet --force --quiet

echo "# Get wordpress-importer with svn"
svn export http://plugins.svn.wordpress.org/wordpress-importer/trunk/ plugins/wordpress-importer --force --quiet

echo "# Get memcached with svn"
svn export http://plugins.svn.wordpress.org/memcached/trunk/object-cache.php content/object-cache-memcached.php --force --quiet

# Create zip package
echo "# Packing $PACKAGE"
zip -qr $PACKAGE $FOLDER dist plugins themes content service -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf themes/minimal
rm -rf plugins/akismet
rm -rf plugins/wordpress-importer
rm content/object-cache-memcached.php
