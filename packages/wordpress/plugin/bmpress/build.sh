NAME="bmpress"
ID="wordpress-plugin-$NAME"
SOURCE="http://code.bmpress.org/svn/trunk/plugins/bmpress/"
FOLDER="plugin"
PACKAGE="$ID.zip"
# Get source
svn export $SOURCE $FOLDER
# Temp
# cp -R /Web/wordpress-trunk/wp-content/plugins/bmpress $FOLDER
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER