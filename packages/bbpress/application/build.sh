NAME="bbpress"
VERSION="1.0.1"
SOURCE="http://svn.automattic.com/$NAME/tags/$VERSION/"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get Source
svn export $SOURCE $FOLDER --force

# Apply patches
patch -p0 -d $FOLDER < patches/global-bb.diff
patch -p0 -d $FOLDER < patches/global-bbdb.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist plugins -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER