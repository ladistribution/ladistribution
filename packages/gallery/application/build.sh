NAME="gallery"
SOURCE="git://github.com/$NAME/gallery3.git"
VERSION="3.0-beta-1"
FOLDER="application"
PACKAGE="$NAME.zip"
# Get source
git clone $SOURCE $FOLDER
rm -rf $FOLDER/.git
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER dist var -x "*/.svn/*"
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER