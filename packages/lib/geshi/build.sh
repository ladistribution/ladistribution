VERSION="1.0.8.11"
FOLDER="lib"
NAME="geshi"
SOURCE="http://freefr.dl.sourceforge.net/project/geshi/geshi/GeSHi%20{$VERSION}/GeSHi-{$VERSION}.tar.gz"
GZ="$NAME.gz"
PACKAGE="lib-geshi.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > $GZ
tar -x -f $GZ
rm $GZ

# Copy files
mkdir $FOLDER
cp $NAME/geshi.php $FOLDER/
cp -R $NAME/geshi $FOLDER/
rm -Rf $NAME

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER