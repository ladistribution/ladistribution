NAME="qtranslate"
VERSION="2.5.5"
SOURCE="http://svn.wp-plugins.org/$NAME/tags/$VERSION/"
FOLDER="plugin"
PACKAGE="wordpress-plugin-$NAME.zip"
# Get source
svn export $SOURCE $FOLDER --force
# Alternate source
# cp -R /Web/wordpress-trunk/wp-content/plugins/qtranslate $FOLDER
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER