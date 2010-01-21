NAME="wordpress-plugin-supercache"
VERSION="0.9.8"
SOURCE="http://svn.wp-plugins.org/wp-super-cache/tags/$VERSION/"
FOLDER="plugin"
PACKAGE="$NAME.zip"
# Get source
svn export $SOURCE $FOLDER
# Rm
rm -rf $FOLDER/plugins
rm -rf $FOLDER/languages
rm -rf $FOLDER/wp-super-cache.pot
# rm -rf $FOLDER/wp-cache-config-sample.php
# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER