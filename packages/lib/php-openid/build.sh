VERSION="2.1.3"
FOLDER="lib"
NAME="php-openid"
SOURCE="http://openidenabled.com/files/$NAME/packages/$NAME-$VERSION.zip"
ZIP="$NAME.zip"
PACKAGE="lib-php-openid.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $ZIP
unzip -q $ZIP
rm $ZIP

cp -R $NAME-$VERSION/Auth $FOLDER
rm -Rf $NAME-$VERSION

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER