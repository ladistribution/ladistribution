NAME="hemingway"
SOURCE="git://github.com/kneath/$NAME.git"
FOLDER="theme"
PACKAGE="wordpress-theme-$NAME.zip"
# Get source
git clone $SOURCE $FOLDER
rm -rf $FOLDER/.git
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../../
#Clean
rm -rf $FOLDER