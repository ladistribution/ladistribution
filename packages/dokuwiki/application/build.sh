NAME="dokuwiki"
VERSION="2009-02-14"
GZ="$NAME.tgz"
SOURCE="http://www.splitbrain.org/_media/projects/$NAME/$NAME-{$VERSION}b.tgz"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
mv $NAME-$VERSION $FOLDER

# Default screenshot
cp screenshot.png $FOLDER/lib/tpl/default/

# Add Minimal theme
mkdir templates
git clone git://github.com/znarf/dokuwiki-minimal.git templates/minimal
rm -rf templates/minimal/.git templates/minimal/Makefile

# Add CSS plugin
git clone git://github.com/znarf/dokuwiki-css.git plugins/css
rm -rf plugins/css/.git

# Apply patches
patch -p0 -d $FOLDER < patches/config.diff
patch -p0 -d $FOLDER < patches/config-feed.diff
patch -p0 -d $FOLDER < patches/geshi.diff
patch -p0 -d $FOLDER < patches/init.diff

# Remove installer
rm $FOLDER/install.php

# Remove default config files
rm $FOLDER/.htaccess.dist
rm $FOLDER/conf/acl.auth.php.dist
rm $FOLDER/conf/local.php.dist
rm $FOLDER/conf/mysql.conf.php.example
rm $FOLDER/conf/users.auth.php.dist
rm $FOLDER/conf/words.aspell.dist

# Remove some default plugins
rm -rf $FOLDER/lib/plugins/importoldchangelog
rm -rf $FOLDER/lib/plugins/importoldindex
rm -rf $FOLDER/lib/plugins/popularity
rm -rf $FOLDER/lib/plugins/plugin

# Remove all lang packs except english
cp -R $FOLDER/inc/lang/en ./en
rm -rf $FOLDER/inc/lang/*
mv ./en $FOLDER/inc/lang/en
#
cp -R $FOLDER/lib/plugins/acl/lang/en ./en
rm -rf $FOLDER/lib/plugins/acl/lang/*
mv ./en $FOLDER/lib/plugins/acl/lang/en
#
cp -R $FOLDER/lib/plugins/config/lang/en ./en
rm -rf $FOLDER/lib/plugins/config/lang/*
mv ./en $FOLDER/lib/plugins/config/lang/en
#
cp -R $FOLDER/lib/plugins/revert/lang/en ./en
rm -rf $FOLDER/lib/plugins/revert/lang/*
mv ./en $FOLDER/lib/plugins/revert/lang/en
#
cp -R $FOLDER/lib/plugins/usermanager/lang/en ./en
rm -rf $FOLDER/lib/plugins/usermanager/lang/*
mv ./en $FOLDER/lib/plugins/usermanager/lang/en

# Remove geshi
rm $FOLDER/inc/geshi.php
rm -rf $FOLDER/inc/geshi

# Remove some unwanted files (osX)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins templates auth -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf templates
rm -rf plugins/css