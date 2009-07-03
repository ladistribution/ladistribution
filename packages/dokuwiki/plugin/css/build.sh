SOURCE="git://github.com/znarf/dokuwiki-css.git"
FOLDER="plugin"
PACKAGE="dokuwiki-plugin-css.zip"
# Get source
git clone $SOURCE $FOLDER
rm -rf $FOLDER/.git
rm -rf $FOLDER/Makefile
# Remove Auth libraries as they are handled as dependencies
rm -Rf $FOLDER/Auth
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER