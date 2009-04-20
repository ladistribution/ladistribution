VERSION="1.0.8.3"
FOLDER="lib"
NAME="geshi"
SOURCE="Location: http://freefr.dl.sourceforge.net/sourceforge/geshi/GeSHi-$VERSION.tar.gz"
GZ="$NAME.gz"
PACKAGE="lib-geshi.zip"
# Get source
curl $SOURCE > $GZ
tar zxvf $GZ
rm $GZ
# Copy files
mkdir $FOLDER
cp $NAME/geshi.php $FOLDER/
cp -R $NAME/geshi $FOLDER/
rm -Rf $NAME
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml
mv $PACKAGE ../../
# Clean
rm -rf $FOLDER