NAME="codecolorer"
VERSION="0.9.7"
SOURCE="http://svn.wp-plugins.org/$NAME/tags/$VERSION/"
FOLDER="plugin"
PACKAGE="wordpress-plugin-$NAME.zip"
# Get source
svn export $SOURCE $FOLDER --force
# Remove Geshi
rm -rf $FOLDER/lib
# Apply Geshi patch
patch -p0 -d $FOLDER < patches/geshi.diff
# Remove locales
# rm -rf $FOLDER/codecolorer-ru_RU.mo
# rm -rf $FOLDER/codecolorer-ru_RU.po
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER