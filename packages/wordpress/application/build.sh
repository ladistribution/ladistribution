NAME="wordpress"
VERSION="2.9.2"
SOURCE="http://svn.automattic.com/$NAME/tags/$VERSION/"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
svn export $SOURCE $FOLDER --force

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
# rm -rf $FOLDER/wp-content/plugins/akismet

# Remove default themes
mkdir themes
rm -rf $FOLDER/wp-content/themes/classic
rm -rf $FOLDER/wp-content/themes/default

# Get i18ned default theme
svn export "http://svn.automattic.com/wordpress-i18n/theme/tags/$VERSION/" "themes/default" --force

# Get Minimal Theme
git clone git://github.com/znarf/wordpress-minimal.git themes/minimal
rm -rf themes/minimal/.git themes/minimal/Makefile

# Get memcache code
svn export http://svn.wp-plugins.org/memcached/trunk/object-cache.php content/object-cache-memcached.php --force

# Remove more files (useless importers)
rm $FOLDER/wp-admin/import/blogware.php
rm $FOLDER/wp-admin/import/greymatter.php
rm $FOLDER/wp-admin/import/stp.php
rm $FOLDER/wp-admin/import/utw.php
rm $FOLDER/wp-admin/import/wp-cat2tag.php

# Apply patches
patch -p0 -d $FOLDER < patches/unserialize-import.diff
patch -p0 -d $FOLDER < patches/menu-header.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins themes content -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf themes
rm content/object-cache-memcached.php
