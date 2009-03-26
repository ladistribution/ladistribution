NAME="wordpress-plugin-openid"
VERSION="3.2.2"
SOURCE="http://svn.wp-plugins.org/openid/tags/$VERSION/"
FOLDER="plugin"
PACKAGE="$NAME.zip"
# Get source
svn export $SOURCE $FOLDER

# Apply patches
patch -p0 -d $FOLDER < patches/class_exists_store.diff

# Remove Auth libraries as they are handled as dependencies
# - but this doesn't works well due to a plugin gotcha
# - we'll remove the folder when this will be fixed upstream
# rm -rf $FOLDER/Auth

# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER