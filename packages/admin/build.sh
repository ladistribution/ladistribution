NAME="admin"
SOURCE="git://github.com/ladistribution/ladistribution.git"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

echo "# Copy 'admin' from tmp"
cp -R tmp/admin $FOLDER

# Clean tmp
rm -rf tmp

echo "# Packing $PACKAGE"
zip -qr $PACKAGE $FOLDER dist -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ..

# Clean
rm -rf $FOLDER
