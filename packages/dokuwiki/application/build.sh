NAME="dokuwiki"
VERSION="2009-12-25"
SUB_VERSION="c"
GZ="$NAME.tgz"
SOURCE="http://www.splitbrain.org/_media/projects/$NAME/$NAME-{$VERSION}{$SUB_VERSION}.tgz"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $GZ
tar -x -f $GZ
rm $GZ
mv $NAME-$VERSION $FOLDER

echo "# Get spam list with curl"
curl http://meta.wikimedia.org/wiki/Spam_blacklist?action=raw -# | grep -v '<pre>' > $FOLDER/conf/wordblock.conf

# Default screenshot
cp screenshot.png $FOLDER/lib/tpl/default/

echo "# Get minimal theme with git"
mkdir templates
git clone git://github.com/znarf/dokuwiki-minimal.git templates/minimal --quiet
rm -rf templates/minimal/.git templates/minimal/Makefile

echo "# Get css plugin with git"
git clone git://github.com/znarf/dokuwiki-css.git plugins/css --quiet
rm -rf plugins/css/.git

echo "# Apply patches"
patch -p0 -d $FOLDER < patches/config.diff
patch -p0 -d $FOLDER < patches/config-feed.diff
patch -p0 -d $FOLDER < patches/geshi.diff
patch -p0 -d $FOLDER < patches/init.diff

echo "# Remove files"

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

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist plugins templates auth -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf templates
rm -rf plugins/css