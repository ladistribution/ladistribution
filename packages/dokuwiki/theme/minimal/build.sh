SOURCE="git://github.com/znarf/dokuwiki-minimal.git"
FOLDER="theme"
PACKAGE="dokuwiki-theme-minimal.zip"
# Get source
git clone $SOURCE $FOLDER
rm -rf $FOLDER/.git
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist
mv $PACKAGE ../../../
# Clean
rm -rf $FOLDER