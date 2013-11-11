NAME="admin"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Copy 'admin' from filesystem"
cp -R ../../admin $FOLDER
rm -rf $FOLDER/.htaccess
rm -rf $FOLDER/dist
rm -rf $FOLDER/openid

echo "# Packing $PACKAGE"
zip -qr $PACKAGE $FOLDER dist -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ..

# Clean
rm -rf $FOLDER
