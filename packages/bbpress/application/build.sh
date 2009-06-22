NAME="bbpress"
VERSION="1.0.2"
GZ="$NAME.tar.gz"
#SOURCE="http://wordpress.org/$NAME-$VERSION.tar.gz"
FOLDER="application"
PACKAGE="$NAME.zip"

# rm -rf $FOLDER

svn export http://svn.automattic.com/bbpress/tags/1.0-rc-3/ $FOLDER

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