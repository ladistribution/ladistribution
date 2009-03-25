VERSION="2.1.2"
FOLDER="lib"
NAME="php-openid"
SOURCE="http://openidenabled.com/files/$NAME/packages/$NAME-$VERSION.zip"
ZIP="$NAME.zip"
PACKAGE="lib-php-openid.zip"
# Get source
curl $SOURCE > $ZIP
unzip $ZIP
rm $ZIP
mkdir $FOLDER
cp -R $NAME-$VERSION/Auth $FOLDER/Auth
rm -Rf $NAME-$VERSION
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER