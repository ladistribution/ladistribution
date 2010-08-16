NAME="bbpress"
VERSION="1.0.2"
SOURCE="http://svn.automattic.com/$NAME/tags/$VERSION/"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
svn export $SOURCE $FOLDER --force --quiet

echo "# Apply patches"
patch -p0 -d $FOLDER < patches/global-bb.diff
patch -p0 -d $FOLDER < patches/global-bbdb.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist plugins -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER