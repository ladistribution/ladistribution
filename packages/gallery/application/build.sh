NAME="gallery3"
VERSION="3.0-rc-1"
# SOURCE="git://github.com/$NAME/gallery3.git"
ZIP="gallery-$VERSION.zip"
SOURCE="http://freefr.dl.sourceforge.net/project/gallery/$NAME/$VERSION/$ZIP"
FOLDER="application"
PACKAGE="gallery.zip"

# Get source GIT
# git clone $SOURCE $FOLDER
# rm -rf $FOLDER/.git

# Get source ZIP
curl $SOURCE > $ZIP
unzip $ZIP
mv $NAME $FOLDER
rm $ZIP

# Apply patch
patch -p0 -d $FOLDER < patches/index.diff

# Remove library handled in dependencies
rm -rf $FOLDER/modules/gallery/lib/HTMLPurifier

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist var modules -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER