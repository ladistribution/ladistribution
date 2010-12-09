NAME="wordpress-plugin-hypercache"
VERSION="2.7.6"
SOURCE="http://svn.wp-plugins.org/hyper-cache/trunk/"
FOLDER="plugin"
PACKAGE="$NAME.zip"
# Get source
svn export $SOURCE $FOLDER
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER