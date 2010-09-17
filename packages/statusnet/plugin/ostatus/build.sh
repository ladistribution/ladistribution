NAME="ostatus"
VERSION="0.9.x"
SOURCE="http://gitorious.org/statusnet/ladistribution/archive-tarball/$VERSION"
FOLDER="plugin"
PACKAGE="statusnet-plugin-$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > statusnet.gz
tar -x -f statusnet.gz
rm statusnet.gz
mv statusnet-ladistribution/plugins/OStatus $FOLDER
rm -rf statusnet-ladistribution

echo "# Packing $PACKAGE"
zip -rqv $PACKAGE $FOLDER dist -q -x \*.svn/\*
mv $PACKAGE ../../../

# Clean
rm -rf $FOLDER
