FOLDER="lib"
PACKAGE="lib-zend-framework.zip"
VERSION="ZendFramework-1.10.5"
NAME="$VERSION-minimal"
ZIP="$NAME.zip"
SOURCE="http://framework.zend.com/releases/$VERSION/$ZIP"

echo "# Building zend-framework package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
rm $ZIP
mv $NAME/library/Zend $FOLDER
rm -rf $NAME

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml --quiet
mv $PACKAGE ./../../

# Clean
rm -rf $FOLDER