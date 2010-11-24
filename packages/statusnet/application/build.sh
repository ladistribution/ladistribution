VERSION="0.9.x"
NAME="statusnet"
FOLDER="application"
SOURCE="http://gitorious.org/statusnet/ladistribution/archive-tarball/$VERSION"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

# Get from GIT
# git clone $SOURCE $FOLDER
# cd $FOLDER
# git checkout -b $VERSION origin/$VERSION
# cd ..
# rm -rf $FOLDER/.git

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > statusnet.gz
tar -x -f statusnet.gz
rm statusnet.gz
mv statusnet-ladistribution $FOLDER

# Get from Github with SVN
# svn checkout http://svn.github.com/znarf/statusnet-ladistribution.git $FOLDER

mv $FOLDER/plugins/Memcache plugins
rm -rf plugins/Memcache/locale

echo "# Remove files"

# Remove unecessary files
rm $FOLDER/.gitignore
rm $FOLDER/Makefile
rm $FOLDER/lighttpd.conf.example
rm $FOLDER/htaccess.sample
rm $FOLDER/config.php.sample
rm $FOLDER/EVENTS.txt
rm $FOLDER/favicon.ico
rm $FOLDER/apple-touch-icon.png
rm $FOLDER/install.php

rm -rf $FOLDER/doc-src
rm -rf $FOLDER/tests
rm -rf $FOLDER/scripts
rm -rf $FOLDER/tpl

rm -rf $FOLDER/locale/*
rm -rf $FOLDER/plugins/*

rm -rf $FOLDER/theme/biz
rm -rf $FOLDER/theme/cloudy
rm -rf $FOLDER/theme/h4ck3r
rm -rf $FOLDER/theme/identica
rm -rf $FOLDER/theme/pigeonthoughts

# Remove Dependencies
rm -rf $FOLDER/extlib/Auth

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -rqv $PACKAGE $FOLDER dist config plugins themes -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm -rf plugins/Memcache
