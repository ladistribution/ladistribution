VERSION="2.2.2"
FOLDER="lib"
NAME="php-openid"
SOURCE="git://github.com/openid/php-openid.git"
ZIP="$NAME.zip"
PACKAGE="lib-php-openid.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet
rm -rf tmp/.git

cp -R tmp/Auth $FOLDER
rm -Rf tmp

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER