NAME="gallery3"
VERSION="3.0"
ZIP="gallery-$VERSION.zip"

FOLDER="application"
PACKAGE="gallery.zip"

echo "# Building $NAME package"

# # Get source GIT
# SOURCE="git://github.com/gallery/gallery3.git"
# git clone $SOURCE $FOLDER
# rm -rf $FOLDER/.git

# Get source ZIP
SOURCE="http://freefr.dl.sourceforge.net/project/gallery/$NAME/$VERSION/$ZIP"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
mv $NAME $FOLDER
rm $ZIP

rm -rf $FOLDER/modules/akismet
rm -rf $FOLDER/modules/digibug
rm -rf $FOLDER/modules/g2_import
rm -rf $FOLDER/modules/recaptcha
rm -rf $FOLDER/modules/server_add
rm -rf $FOLDER/modules/watermark

echo "# Applying patches"
patch -p0 -d $FOLDER < patches/index.diff

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -rqv $PACKAGE $FOLDER dist var modules -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER