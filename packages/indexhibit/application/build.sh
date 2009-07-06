APP="indexhibit"
FOLDER="application"
PACKAGE="$APP.zip"
VERSION="v070e"
NAME="$APP$VERSION"
ZIP="$NAME.zip"
SOURCE="http://www.indexhibit.org/script/downloader.script.php?id=1"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP
rm $ZIP
mv $NAME $FOLDER
rm -rf $FOLDER/files
rm $FOLDER/htaccess
rm $FOLDER/ndxz-studio/config/config.example.php
rf -rf __MACOSX
# Apply patches
patch -p0 -d $FOLDER < patches/defaults.diff
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER