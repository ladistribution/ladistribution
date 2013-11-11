FOLDER="lib"
PACKAGE="lib-zend-framework.zip"
VERSION="ZendFramework-1.12.3"
NAME="$VERSION-minimal"
ZIP="$NAME.zip"
SOURCE="https://packages.zendframework.com/releases/$VERSION/$ZIP"

echo "# Building zend-framework package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
rm $ZIP
mv $NAME/library/Zend $FOLDER
rm -rf $NAME

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ./../../

# Clean
rm -rf $FOLDER