NAME="wordpress"
VERSION="3.1"
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
rm $FOLDER/wp-atom.php
rm $FOLDER/wp-commentsrss2.php
rm $FOLDER/wp-config-sample.php
rm $FOLDER/wp-feed.php
rm $FOLDER/wp-rdf.php
rm $FOLDER/wp-rss.php
rm $FOLDER/wp-rss2.php
rm $FOLDER/wp-register.php
rm $FOLDER/wp-content/plugins/hello.php

# Remove default themes
rm -rf $FOLDER/wp-content/themes/classic
rm -rf $FOLDER/wp-content/themes/default

# echo "# Get kubrick theme with svn"
# svn export "http://svn.automattic.com/wordpress-i18n/theme/trunk/" "themes/default" --force --quiet

echo "# Get minimal theme with git"
git clone git://github.com/znarf/wordpress-minimal.git themes/minimal --quiet
rm -rf themes/minimal/.git themes/minimal/Makefile

echo "# Get akismet with svn"
svn export http://plugins.svn.wordpress.org/akismet/trunk/ plugins/akismet --force --quiet

echo "# Get wordpress-importer with svn"
svn export http://plugins.svn.wordpress.org/wordpress-importer/trunk/ plugins/wordpress-importer --force --quiet

echo "# Get memcached with svn"
svn export http://svn.wp-plugins.org/memcached/trunk/object-cache.php content/object-cache-memcached.php --force --quiet

# Apply patches
# patch -p0 -d $FOLDER < patches/menu-header.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist plugins themes content service -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf themes/minimal
rm -rf plugins/akismet
rm -rf plugins/wordpress-importer
rm content/object-cache-memcached.php
