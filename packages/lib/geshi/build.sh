VERSION="1.0.8.4"
FOLDER="lib"
NAME="geshi"
SOURCE="http://freefr.dl.sourceforge.net/project/geshi/geshi/GeSHi%20{$VERSION}/GeSHi-{$VERSION}.tar.gz"
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