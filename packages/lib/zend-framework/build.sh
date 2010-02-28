FOLDER="lib"
PACKAGE="lib-zend-framework.zip"
VERSION="ZendFramework-1.10.2"
NAME="$VERSION-minimal"
ZIP="$NAME.zip"

# Get source
curl http://framework.zend.com/releases/$VERSION/$ZIP > $ZIP
unzip $ZIP
rm $ZIP
mv $NAME/library $FOLDER
rm -rf $NAME

# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ./../../

# Clean
rm -rf $FOLDER